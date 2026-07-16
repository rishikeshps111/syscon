<?php

namespace App\Http\Controllers;

use App\Models\BranchLocation;
use App\Models\ControllerProfile;
use App\Models\Depot;
use App\Models\Designation;
use App\Models\District;
use App\Models\DriverProfile;
use App\Models\Location;
use App\Models\Oem;
use App\Models\StaffProfile;
use App\Models\State;
use App\Models\SupervisorProfile;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\SalaryComponents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BulkImportController extends Controller
{
    public function form(string $module)
    {
        $config = $this->config($module);
        $this->authorizeModule($config);

        return view('bulk-import.form', compact('module', 'config'));
    }

    public function sample(string $module)
    {
        $config = $this->config($module);
        $this->authorizeModule($config);

        return response()->streamDownload(function () use ($config) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $config['headers']);
            fputcsv($handle, $config['sample']);
            fclose($handle);
        }, $module . '-import-sample.csv', ['Content-Type' => 'text/csv']);
    }

    public function import(Request $request, string $module)
    {
        $config = $this->config($module);
        $this->authorizeModule($config);
        $request->validate(['csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);

        [$rows, $readErrors] = $this->readCsv($request->file('csv_file')->getRealPath(), $config['headers']);
        if ($readErrors) {
            throw ValidationException::withMessages(['csv_file' => $readErrors]);
        }

        [$validatedRows, $errors] = $this->validateRows($module, $rows, $config);
        if ($errors) {
            throw ValidationException::withMessages(['csv_file' => $errors]);
        }

        DB::transaction(function () use ($module, $validatedRows) {
            foreach ($validatedRows as $data) {
                $module === 'vehicles' ? $this->createVehicle($data) : $this->createPerson($module, $data);
            }
        });

        return redirect()->route($config['index_route'])
            ->with('success', count($validatedRows) . ' ' . Str::lower($config['label']) . ' record(s) imported successfully.');
    }

    private function validateRows(string $module, array $rows, array $config): array
    {
        $valid = [];
        $errors = [];
        $seen = [];

        foreach ($rows as $row) {
            $data = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $row['data']);
            $data = $this->resolveRelations($data, $module, $row['line'], $errors);
            $data = $this->normalize($data, $module);
            $validator = Validator::make($data, $this->rules($module));

            foreach ($validator->errors()->all() as $message) {
                $errors[] = "Row {$row['line']}: {$message}";
            }

            foreach ($config['unique_csv'] as $field) {
                $value = Str::lower((string) ($data[$field] ?? ''));
                if ($value === '') {
                    continue;
                }
                if (isset($seen[$field][$value])) {
                    $errors[] = "Row {$row['line']}: duplicate {$field}; first seen on row {$seen[$field][$value]}.";
                }
                $seen[$field][$value] = $row['line'];
            }

            $valid[] = $data;
        }

        return [$valid, array_values(array_unique($errors))];
    }

    private function resolveRelations(array $data, string $module, int $line, array &$errors): array
    {
        $lookups = [
            'state' => [State::class, 'name', 'state_id'],
            'district' => [District::class, 'name', 'district_id'],
            'location' => [Location::class, 'name', 'location_id'],
            'depot' => [Depot::class, 'name', 'depot_id'],
        ];
        if ($module === 'vehicles') {
            $lookups += [
                'oem' => [Oem::class, 'oem_name', 'oem_id'],
                'branch' => [BranchLocation::class, 'name', 'branch_id'],
            ];
        } elseif ($module === 'drivers') {
            $lookups['branch'] = [BranchLocation::class, 'name', 'branch_location_id'];
        } elseif ($module === 'staff') {
            $lookups['designation'] = [Designation::class, 'name', 'designation_id'];
        }

        foreach ($lookups as $csvField => [$model, $column, $idField]) {
            $name = $data[$csvField] ?? '';
            if ($name === '') {
                $data[$idField] = null;
                continue;
            }
            $query = $model::query()->whereRaw('LOWER(' . $column . ') = ?', [Str::lower($name)]);
            if ($csvField === 'district' && ! empty($data['state_id'])) {
                $query->where('state_id', $data['state_id']);
            }
            if ($csvField === 'location') {
                $query->when(! empty($data['state_id']), fn ($q) => $q->where('state_id', $data['state_id']))
                    ->when(! empty($data['district_id']), fn ($q) => $q->where('district_id', $data['district_id']));
            }
            $matches = $query->limit(2)->get();
            if ($matches->count() !== 1) {
                $errors[] = "Row {$line}: {$csvField} '{$name}' " . ($matches->isEmpty() ? 'was not found.' : 'is ambiguous.');
                $data[$idField] = null;
            } else {
                $data[$idField] = $matches->first()->id;
            }
        }

        return $data;
    }

    private function normalize(array $data, string $module): array
    {
        foreach (['is_active', 'gps_enabled'] as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = $this->booleanValue($data[$field]);
            }
        }
        foreach ($data as $key => $value) {
            if ($value === '') {
                $data[$key] = null;
            }
        }
        if ($module === 'vehicles') {
            foreach (['vehicle_no', 'chassis_no', 'engine_no'] as $field) {
                $data[$field] = isset($data[$field]) ? Str::upper($data[$field]) : null;
            }
        }
        return $data;
    }

    private function createVehicle(array $data): void
    {
        $payload = collect($data)->only($this->config('vehicles')['database_fields'])->all();
        if (($payload['fuel_type'] ?? null) !== 'ELECTRIC') {
            $payload['battery_capacity'] = $payload['range_km'] = null;
        }
        if (empty($payload['gps_enabled'])) {
            $payload['gps_imei'] = null;
        }
        $vehicle = Vehicle::create($payload + ['vehicle_code' => null, 'is_verified' => false, 'created_by' => auth()->id(), 'updated_by' => auth()->id()]);
        $vehicle->update(['vehicle_code' => generate_code('Vehicle Module', $vehicle->id, 3, 'VEH')]);
    }

    private function createPerson(string $module, array $data): void
    {
        $meta = [
            'drivers' => ['role' => 'Driver', 'relation' => 'driverProfile', 'code' => ['Driver Management Module', 'DRV']],
            'controllers' => ['role' => 'Controller', 'relation' => 'controllerProfile', 'code' => ['Controller Management Module', 'CTL']],
            'supervisors' => ['role' => 'Supervisor', 'relation' => 'supervisorProfile', 'code' => ['Supervisor Management Module', 'SUP']],
            'staff' => ['role' => 'Staff', 'relation' => 'staffProfile', 'code' => ['Staff Management Module', 'STF']],
        ][$module];
        $passwordField = $module === 'staff' ? 'password' : 'passcode';
        $user = User::create(collect($data)->only(['name', 'email', 'country_code', 'phone', 'is_active'])->all() + [
            'code' => null, 'password' => $data[$passwordField],
        ]);
        $user->update(['code' => generate_code($meta['code'][0], $user->id, 3, $meta['code'][1])]);
        $profile = collect($data)->only($this->config($module)['profile_fields'])->all();
        $salary = SalaryComponents::legacyProfileSalaryData([]);
        if ($module === 'drivers') {
            $profile['salary'] = $salary['salary'];
        } else {
            $profile += collect($salary)->only(['basic', 'vda', 'basic_vda', 'hra', 'special_allowance', 'conveyance_allowance', 'bonus', 'gross_salary'])->all();
        }
        $user->{$meta['relation']}()->create($profile);
        if ($module === 'staff') {
            $roles = ['Staff'];
            $designation = Designation::with('role')->find($data['designation_id']);
            if ($designation?->role) $roles[] = $designation->role->name;
            $user->syncRoles($roles);
        } else {
            $user->assignRole($meta['role']);
        }
    }

    private function rules(string $module): array
    {
        if ($module === 'vehicles') {
            return [
                'state_id' => ['required'], 'oem_id' => ['required'], 'depot_id' => ['required'], 'branch_id' => ['required'],
                'vehicle_no' => ['required', 'max:20', 'unique:vehicles,vehicle_no'],
                'vehicle_type' => ['required', Rule::in(array_keys(Vehicle::TYPES))],
                'fuel_type' => ['required', Rule::in(array_keys(Vehicle::FUEL_TYPES))],
                'vehicle_category' => ['required', Rule::in(array_keys(Vehicle::CATEGORIES))],
                'make' => ['required', 'max:255'], 'model' => ['required', 'max:255'], 'variant' => ['nullable', 'max:255'],
                'capacity_seating' => ['nullable', 'integer', 'min:0'], 'capacity_load' => ['nullable', 'numeric', 'min:0'],
                'battery_capacity' => ['nullable', 'numeric', 'min:0', 'required_if:fuel_type,ELECTRIC'],
                'range_km' => ['nullable', 'integer', 'min:0', 'required_if:fuel_type,ELECTRIC'],
                'engine_no' => ['nullable', 'max:255'], 'chassis_no' => ['required', 'max:255', 'unique:vehicles,chassis_no'],
                'registration_date' => ['nullable', 'date'], 'registration_valid_upto' => ['nullable', 'date', 'after_or_equal:registration_date'],
                'fitness_expiry' => ['nullable', 'date'], 'permit_expiry' => ['nullable', 'date'], 'insurance_expiry' => ['nullable', 'date'], 'pollution_expiry' => ['nullable', 'date'],
                'gps_enabled' => ['required', 'boolean'], 'gps_imei' => ['nullable', 'max:255', 'required_if:gps_enabled,1'],
                'status' => ['required', Rule::in(array_keys(Vehicle::STATUSES))], 'remarks' => ['nullable'],
            ];
        }
        $rules = [
            'name' => ['required', 'max:255'], 'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'country_code' => ['required', 'max:10'], 'phone' => ['required', 'max:30'], 'is_active' => ['required', 'boolean'],
            'depot_id' => ['required'], 'employment_type' => ['required'], 'country' => ['required', 'max:100'],
            'state_id' => ['required'], 'district_id' => ['required'], 'location_id' => ['required'],
        ];
        if ($module === 'drivers') {
            return $rules + [
                'passcode' => ['required', 'digits:6'], 'alternate_country_code' => ['nullable', 'max:10'], 'alternate_phone' => ['nullable', 'max:30'],
                'aadhaar_number' => ['required', 'max:20', 'unique:driver_profiles,aadhaar_number'], 'pincode' => ['required', 'max:10'], 'address' => ['required', 'max:1000'],
                'license_number' => ['required', 'max:50', 'unique:driver_profiles,license_number'], 'license_type' => ['required', Rule::in(array_keys(DriverProfile::LICENSE_TYPES))],
                'issue_date' => ['required', 'date'], 'expiry_date' => ['required', 'date', 'after_or_equal:issue_date'], 'badge_number' => ['nullable', 'max:50'], 'badge_expiry_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
                'employment_type' => ['required', Rule::in(array_keys(DriverProfile::EMPLOYMENT_TYPES))], 'joining_date' => ['required', 'date'], 'branch_location_id' => ['required'],
                'account_number' => ['required', 'max:50'], 'ifsc_code' => ['required', 'max:20'], 'emergency_contact_name' => ['required', 'max:255'],
                'emergency_country_code' => ['required', 'max:10'], 'emergency_contact_no' => ['required', 'max:30'], 'medical_fitness_expiry' => ['required', 'date'],
                'police_verification_status' => ['required', Rule::in(array_keys(DriverProfile::VERIFICATION_STATUSES))], 'verification_status' => ['required', Rule::in(array_keys(DriverProfile::VERIFICATION_STATUSES))],
            ];
        }
        $class = ['controllers' => ControllerProfile::class, 'supervisors' => SupervisorProfile::class, 'staff' => StaffProfile::class][$module];
        $rules += [
            ($module === 'staff' ? 'password' : 'passcode') => $module === 'staff' ? ['required', 'min:8'] : ['required', 'digits:6'],
            'employment_type' => ['required', Rule::in(array_keys($class::EMPLOYMENT_TYPES))], 'father_name' => ['required', 'max:255'],
            'date_of_birth' => ['required', 'date'], 'aadhaar_number' => ['required', 'max:20'], 'pan_number' => ['required', 'max:20'],
            'date_of_joining' => ['required', 'date'], 'uan' => ['required', 'max:50'], 'esic_wc' => ['required', 'max:50'],
            'bank_account_number' => ['required', 'max:50'], 'ifsc_code' => ['required', 'max:20'],
        ];
        if ($module === 'staff') {
            $rules += ['designation_id' => ['required'], 'category' => ['required', Rule::in(array_keys(StaffProfile::CATEGORIES))]];
        }
        return $rules;
    }

    private function readCsv(string $path, array $expected): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) return [[], ['Unable to read the uploaded CSV file.']];
        $header = fgetcsv($handle);
        if (! $header) return [[], ['CSV file is empty.']];
        $header = array_map(fn ($v) => Str::of((string) $v)->trim()->lower()->replace([' ', '-'], '_')->toString(), $header);
        $missing = array_diff($expected, $header);
        if ($missing) { fclose($handle); return [[], ['Missing column(s): ' . implode(', ', $missing) . '.']]; }
        $rows = []; $errors = []; $line = 1;
        while (($values = fgetcsv($handle)) !== false) {
            $line++;
            if (count(array_filter($values, fn ($v) => trim((string) $v) !== '')) === 0) continue;
            if (count($values) > count($header)) { $errors[] = "Row {$line}: too many columns."; continue; }
            $rows[] = ['line' => $line, 'data' => array_combine($header, array_pad($values, count($header), ''))];
        }
        fclose($handle);
        if (! $rows && ! $errors) $errors[] = 'CSV file does not contain any data rows.';
        return [$rows, $errors];
    }

    private function booleanValue(mixed $value): mixed
    {
        $value = Str::lower(trim((string) $value));
        return match ($value) { '1', 'yes', 'true', 'active', 'enabled' => 1, '0', 'no', 'false', 'inactive', 'disabled' => 0, default => $value };
    }

    private function authorizeModule(array $config): void
    {
        abort_unless(auth()->user()?->can($config['permission']), 403);
    }

    private function config(string $module): array
    {
        $commonPerson = ['name','email','country_code','phone','depot','employment_type','is_active','father_name','date_of_birth','aadhaar_number','pan_number','date_of_joining','uan','esic_wc','country','state','district','location','bank_account_number','ifsc_code'];
        $personProfile = ['depot_id','employment_type','father_name','date_of_birth','aadhaar_number','pan_number','date_of_joining','uan','esic_wc','country','state_id','district_id','location_id','bank_account_number','ifsc_code'];
        $configs = [
            'vehicles' => [
                'label'=>'Vehicles','permission'=>'vehicles.create','index_route'=>'vehicles.index',
                'headers'=>['state','oem','depot','branch','vehicle_no','vehicle_type','fuel_type','vehicle_category','make','model','variant','capacity_seating','capacity_load','battery_capacity','range_km','engine_no','chassis_no','registration_date','registration_valid_upto','fitness_expiry','permit_expiry','insurance_expiry','pollution_expiry','gps_enabled','gps_imei','status','remarks'],
                'sample'=>['Maharashtra','Sample OEM','Central Depot','Main Branch','MH12AB1234','BUS','DIESEL','Passenger','Tata','Starbus','','40','','','','ENG001','CHS001','2026-01-01','2031-01-01','2027-01-01','2027-01-01','2027-01-01','2027-01-01','no','','Active',''],
                'unique_csv'=>['vehicle_no','chassis_no'],
                'database_fields'=>['state_id','oem_id','depot_id','branch_id','vehicle_no','vehicle_type','fuel_type','vehicle_category','make','model','variant','capacity_seating','capacity_load','battery_capacity','range_km','engine_no','chassis_no','registration_date','registration_valid_upto','fitness_expiry','permit_expiry','insurance_expiry','pollution_expiry','gps_enabled','gps_imei','status','remarks'],
            ],
            'drivers' => [
                'label'=>'Drivers','permission'=>'driver-management.create','index_route'=>'driver-management.index',
                'headers'=>['name','country_code','phone','alternate_country_code','alternate_phone','email','passcode','is_active','aadhaar_number','country','state','district','location','pincode','address','license_number','license_type','issue_date','expiry_date','badge_number','badge_expiry_date','employment_type','joining_date','depot','branch','account_number','ifsc_code','emergency_contact_name','emergency_country_code','emergency_contact_no','medical_fitness_expiry','police_verification_status','verification_status'],
                'sample'=>['Sample Driver','+91','9876543210','','','driver@example.com','123456','yes','123456789012','India','Maharashtra','Pune','Pune','411001','Sample address','DL001','hmv','2024-01-01','2029-01-01','','','permanent','2026-01-01','Central Depot','Main Branch','1234567890','ABCD0001234','Contact Person','+91','9876543211','2027-01-01','verified','verified'],
                'unique_csv'=>['email','aadhaar_number','license_number'],
                'profile_fields'=>['alternate_country_code','alternate_phone','aadhaar_number','country','state_id','district_id','location_id','pincode','address','license_number','license_type','issue_date','expiry_date','badge_number','badge_expiry_date','employment_type','joining_date','depot_id','branch_location_id','account_number','ifsc_code','emergency_contact_name','emergency_country_code','emergency_contact_no','medical_fitness_expiry','police_verification_status','verification_status'],
            ],
        ];
        foreach (['controllers'=>['Controller','Controller Management Module','CTL'], 'supervisors'=>['Supervisor','Supervisor Management Module','SUP'], 'staff'=>['Staff','Staff Management Module','STF']] as $key => $meta) {
            $extra = $key === 'staff' ? ['password','designation','category'] : ['passcode'];
            $headers = array_merge(array_slice($commonPerson, 0, 4), $extra, array_slice($commonPerson, 4));
            $sampleExtra = $key === 'staff' ? ['password123','Manager','skilled'] : ['123456'];
            $configs[$key] = [
                'label'=>$meta[0] . 's','permission'=>Str::singular($key) . '-management.create','index_route'=>Str::singular($key) . '-management.index',
                'headers'=>$headers,
                'sample'=>array_merge(['Sample ' . $meta[0], $key . '@example.com','+91','9876543210'], $sampleExtra, ['Central Depot','full_time','yes','Father Name','1990-01-01','123456789012','ABCDE1234F','2026-01-01','UAN001','ESIC001','India','Maharashtra','Pune','Pune','1234567890','ABCD0001234']),
                'unique_csv'=>['email'], 'profile_fields'=>$key === 'staff' ? array_merge($personProfile, ['designation_id','category']) : $personProfile,
            ];
        }
        abort_unless(isset($configs[$module]), 404);
        $optional = match ($module) {
            'vehicles' => ['variant', 'capacity_seating', 'capacity_load', 'battery_capacity', 'range_km', 'engine_no', 'registration_date', 'registration_valid_upto', 'fitness_expiry', 'permit_expiry', 'insurance_expiry', 'pollution_expiry', 'gps_imei', 'remarks'],
            'drivers' => ['alternate_country_code', 'alternate_phone', 'badge_number', 'badge_expiry_date'],
            default => [],
        };
        $configs[$module]['instructions'] = collect($configs[$module]['headers'])
            ->map(fn (string $column) => [
                'column' => $column,
                'required' => in_array($column, ['battery_capacity', 'range_km', 'gps_imei'], true)
                    ? 'Conditional'
                    : (in_array($column, $optional, true) ? 'No' : 'Yes'),
                'instruction' => $this->columnInstruction($column, $module),
            ])->all();

        return $configs[$module];
    }

    private function columnInstruction(string $column, string $module): string
    {
        $instructions = [
            'name' => 'Full name, maximum 255 characters.',
            'email' => 'Valid and unique email address. It must not already belong to another user or appear twice in the CSV.',
            'country_code' => 'Telephone country code, for example +91.',
            'phone' => 'Primary phone number, maximum 30 characters.',
            'alternate_country_code' => 'Optional alternate telephone country code, for example +91.',
            'alternate_phone' => 'Optional alternate phone number, maximum 30 characters.',
            'passcode' => 'Exactly 6 digits. This becomes the initial login passcode.',
            'password' => 'Initial login password containing at least 8 characters.',
            'is_active' => 'Use yes/no, true/false, active/inactive, or 1/0.',
            'father_name' => 'Father name, maximum 255 characters.',
            'date_of_birth' => 'Use YYYY-MM-DD.',
            'aadhaar_number' => $module === 'drivers' ? 'Aadhaar number, maximum 20 characters; must be unique.' : 'Aadhaar number, maximum 20 characters.',
            'pan_number' => 'PAN number, maximum 20 characters.',
            'date_of_joining' => 'Use YYYY-MM-DD.',
            'joining_date' => 'Use YYYY-MM-DD.',
            'uan' => 'UAN, maximum 50 characters.',
            'esic_wc' => 'ESIC/WC value, maximum 50 characters.',
            'country' => 'Country name, for example India.',
            'state' => 'Exact existing state name. Do not use an ID.',
            'district' => 'Exact existing district name belonging to the specified state. Do not use an ID.',
            'location' => 'Exact existing location name belonging to the specified state and district. Do not use an ID.',
            'depot' => 'Exact existing depot name. Do not use an ID.',
            'branch' => 'Exact existing branch-location name. Do not use an ID.',
            'designation' => 'Exact existing designation name. Do not use an ID.',
            'employment_type' => $module === 'drivers' ? 'Use permanent or contract.' : 'Use full_time, part_time, or contract.',
            'category' => 'Use skilled, unskilled, or managerial.',
            'bank_account_number' => 'Bank account number, maximum 50 characters.',
            'account_number' => 'Bank account number, maximum 50 characters.',
            'ifsc_code' => 'Bank IFSC code, maximum 20 characters.',
            'pincode' => 'Postal PIN code, maximum 10 characters.',
            'address' => 'Full address, maximum 1,000 characters.',
            'license_number' => 'Driving licence number, maximum 50 characters; must be unique.',
            'license_type' => 'Use lmv, hmv, or transport.',
            'issue_date' => 'Licence issue date in YYYY-MM-DD format.',
            'expiry_date' => 'Licence expiry date in YYYY-MM-DD format; cannot be before issue_date.',
            'badge_number' => 'Optional badge number, maximum 50 characters.',
            'badge_expiry_date' => 'Optional badge expiry date in YYYY-MM-DD format; cannot be before issue_date.',
            'emergency_contact_name' => 'Emergency contact person name, maximum 255 characters.',
            'emergency_country_code' => 'Emergency telephone country code, for example +91.',
            'emergency_contact_no' => 'Emergency contact number, maximum 30 characters.',
            'medical_fitness_expiry' => 'Use YYYY-MM-DD.',
            'police_verification_status' => 'Use pending, verified, or rejected.',
            'verification_status' => 'Use pending, verified, or rejected.',
            'oem' => 'Exact existing OEM name. Do not use an ID.',
            'vehicle_no' => 'Unique vehicle registration number, maximum 20 characters. It is converted to uppercase.',
            'vehicle_type' => 'Use BUS, CAR, VAN, TRUCK, or AUTO.',
            'fuel_type' => 'Use ELECTRIC, DIESEL, PETROL, CNG, or HYBRID.',
            'vehicle_category' => 'Use Passenger or Cargo.',
            'make' => 'Vehicle manufacturer/make, maximum 255 characters.',
            'model' => 'Vehicle model, maximum 255 characters.',
            'variant' => 'Optional vehicle variant, maximum 255 characters.',
            'capacity_seating' => 'Optional whole number of seats, 0 or greater.',
            'capacity_load' => 'Optional numeric load capacity, 0 or greater.',
            'battery_capacity' => 'Required only when fuel_type is ELECTRIC; use a number 0 or greater.',
            'range_km' => 'Required only when fuel_type is ELECTRIC; use a whole number 0 or greater.',
            'engine_no' => 'Optional engine number, maximum 255 characters. It is converted to uppercase.',
            'chassis_no' => 'Unique chassis number, maximum 255 characters. It is converted to uppercase.',
            'registration_date' => 'Optional date in YYYY-MM-DD format.',
            'registration_valid_upto' => 'Optional date in YYYY-MM-DD format; cannot be before registration_date.',
            'fitness_expiry' => 'Optional date in YYYY-MM-DD format.',
            'permit_expiry' => 'Optional date in YYYY-MM-DD format.',
            'insurance_expiry' => 'Optional date in YYYY-MM-DD format.',
            'pollution_expiry' => 'Optional date in YYYY-MM-DD format.',
            'gps_enabled' => 'Use yes/no, true/false, enabled/disabled, or 1/0.',
            'gps_imei' => 'Required when gps_enabled is yes; otherwise leave blank.',
            'status' => 'Use Active, Inactive, Under Maintenance, or Scrap.',
            'remarks' => 'Optional remarks or notes.',
        ];

        return $instructions[$column] ?? 'Enter the value shown in the add/edit form.';
    }
}
