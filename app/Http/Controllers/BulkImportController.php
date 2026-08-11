<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\BranchLocation;
use App\Models\ControllerProfile;
use App\Models\Depot;
use App\Models\Designation;
use App\Models\District;
use App\Models\DriverProfile;
use App\Models\HousekeepingProfile;
use App\Models\Location;
use App\Models\Oem;
use App\Models\StaffProfile;
use App\Models\State;
use App\Models\SupervisorProfile;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\SalaryComponents;
use App\Support\UserCodeGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BulkImportController extends Controller
{
    private const DEFAULT_STAFF_PASSWORD = 'Syscon@123';

    private const DEFAULT_OPERATIONS_PASSCODE = '111111';

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

        if ($module === 'staff') {
            return response()->streamDownload(function () use ($config) {
                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle('Staff Import');
                $sheet->fromArray($config['sample_headers'], null, 'A1');
                $sheet->fromArray($config['sample'], null, 'A2');
                $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->getFont()->setBold(true);
                foreach (range('A', $sheet->getHighestColumn()) as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }
                (new Xlsx($spreadsheet))->save('php://output');
                $spreadsheet->disconnectWorksheets();
            }, 'staff-import-sample.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }

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
        $request->validate([
            'csv_file' => ['required', 'file', $module === 'staff' ? 'mimes:xlsx,xls,csv,txt' : 'mimes:csv,txt', 'max:5120'],
        ]);

        $file = $request->file('csv_file');
        [$rows, $readErrors] = in_array(Str::lower($file->getClientOriginalExtension()), ['xlsx', 'xls'], true)
            ? $this->readSpreadsheet($file->getRealPath(), $config['headers'])
            : $this->readCsv($file->getRealPath(), $config['headers']);
        if ($readErrors) {
            throw ValidationException::withMessages(['csv_file' => $readErrors]);
        }

        [$validatedRows, $errors] = $this->validateRows($module, $rows, $config);
        if ($errors) {
            throw ValidationException::withMessages(['csv_file' => $errors]);
        }

        DB::transaction(function () use ($module, $validatedRows) {
            foreach ($validatedRows as $data) {
                match ($module) {
                    'vehicles' => $this->createVehicle($data),
                    'designations' => $this->createDesignation($data),
                    default => $this->createPerson($module, $data),
                };
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
        } elseif ($module === 'designations') {
            $lookups = [
                'department' => [\App\Models\Department::class, 'name', 'department_id'],
                'level' => [\App\Models\Level::class, 'name', 'level_id'],
                'reporting_to' => [Role::class, 'name', 'reporting_to'],
            ];
        } elseif (in_array($module, ['drivers', 'housekeeping'], true)) {
        } elseif ($module === 'staff') {
            $lookups['designation'] = [Designation::class, 'name', 'designation_id'];
            $lookups['branch'] = [BranchLocation::class, 'name', 'branch_location_id'];
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
            if ($csvField === 'reporting_to' && $module === 'designations') {
                $query->where('guard_name', 'web')
                    ->whereIn('name', ['Staff', 'Driver', 'Controller', 'Supervisor']);
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
        if ($module === 'staff') {
            $data['is_active'] = $data['status'] ?? null;
            $data['bank_account_number'] = $data['account_number'] ?? null;
            [$data['country_code'], $data['phone']] = $this->splitPhone((string) ($data['phone'] ?? ''));

            foreach (['date_of_birth', 'date_of_joining'] as $dateField) {
                if (filled($data[$dateField] ?? null)) {
                    try {
                        $data[$dateField] = Carbon::parse($data[$dateField])->format('Y-m-d');
                    } catch (\Throwable) {
                        // Leave the original value for the validator to report.
                    }
                }
            }
        }

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

    private function createDesignation(array $data): void
    {
        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        $designation = Designation::create(collect($data)->only([
            'department_id',
            'level_id',
            'reporting_to',
            'name',
            'description',
            'is_active',
        ])->all() + ['role_id' => $role->id]);

        $designation->update([
            'code' => generate_code('Designation Module', $designation->id, 3, 'DSG'),
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function createPerson(string $module, array $data): void
    {
        if ($module === 'staff') {
            $this->createUnifiedEmployee($data);
            return;
        }
        $meta = [
            'drivers' => ['role' => 'Driver', 'relation' => 'driverProfile'],
            'housekeeping' => ['role' => 'Housekeeping', 'relation' => 'housekeepingProfile'],
            'controllers' => ['role' => 'Controller', 'relation' => 'controllerProfile'],
            'supervisors' => ['role' => 'Supervisor', 'relation' => 'supervisorProfile'],
            'staff' => ['role' => 'Staff', 'relation' => 'staffProfile'],
        ][$module];
        $passwordField = $module === 'staff' ? 'password' : 'passcode';
        $userData = collect($data)->only(['name', 'email', 'country_code', 'phone', 'is_active'])->all();
        $userData['email'] = filled($userData['email'] ?? null) ? $userData['email'] : null;
        $user = User::create($userData + [
            'code' => null, 'password' => $module === 'housekeeping' ? Str::random(40) : $data[$passwordField],
        ]);
        $user->update([
            'code' => UserCodeGenerator::generate($meta['role'], (int) $data['depot_id'], $user->id),
        ]);
        $profile = collect($data)->only($this->config($module)['profile_fields'])->all();
        $salary = SalaryComponents::legacyProfileSalaryData([]);
        if (in_array($module, ['drivers', 'housekeeping'], true)) {
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

    private function createUnifiedEmployee(array $data): void
    {
        $role = $data['role'];
        $credential = match ($role) {
            'Staff' => self::DEFAULT_STAFF_PASSWORD,
            'Controller', 'Supervisor' => self::DEFAULT_OPERATIONS_PASSCODE,
            default => Str::random(40),
        };
        $user = User::create([
            'code' => null, 'ref_code' => $data['ref_code'] ?? null, 'name' => $data['name'],
            'email' => $data['email'] ?? null, 'country_code' => $data['country_code'], 'phone' => $data['phone'],
            'is_active' => $data['is_active'], 'password' => $credential,
        ]);
        $user->update(['code' => UserCodeGenerator::generate($role, (int) $data['depot_id'], $user->id)]);
        $salary = SalaryComponents::legacyProfileSalaryData([]);
        $common = collect($data)->only([
            'depot_id', 'employment_type', 'father_name', 'date_of_birth', 'aadhaar_number', 'pan_number',
            'date_of_joining', 'uan', 'esic_wc', 'country', 'state_id', 'district_id', 'location_id',
            'bank_account_number', 'ifsc_code',
        ])->merge(collect($salary)->only(['basic','vda','basic_vda','hra','special_allowance','conveyance_allowance','bonus','gross_salary']))->all();

        if ($role === 'Staff') {
            $user->staffProfile()->create($common + ['designation_id' => $data['designation_id'], 'category' => null]);
            $roles = ['Staff'];
            $designationRole = Designation::with('role')->find($data['designation_id'])?->role?->name;
            if ($designationRole) $roles[] = $designationRole;
            $user->syncRoles($roles);
        } elseif ($role === 'Housekeeping') {
            unset($common['date_of_joining'], $common['bank_account_number']);
            $user->housekeepingProfile()->create($common + collect($data)->only([
                'branch_location_id','pincode','address','emergency_contact_name','emergency_country_code',
                'emergency_contact_no','medical_fitness_expiry','police_verification_status','verification_status',
            ])->all() + ['joining_date' => $data['date_of_joining'], 'account_number' => $data['bank_account_number'], 'salary' => $salary['salary']]);
            $user->assignRole($role);
        } else {
            $relation = $role === 'Controller' ? 'controllerProfile' : 'supervisorProfile';
            $user->{$relation}()->create($common);
            $user->assignRole($role);
        }
    }

    private function rules(string $module): array
    {
        if ($module === 'designations') {
            return [
                'department_id' => ['required', 'integer', 'exists:departments,id'],
                'level_id' => ['required', 'integer', 'exists:levels,id'],
                'reporting_to' => [
                    'nullable',
                    'integer',
                    Rule::exists('roles', 'id')->where(
                        fn ($query) => $query->whereIn('name', ['Staff', 'Driver', 'Controller', 'Supervisor'])
                    ),
                ],
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    'unique:designations,name',
                    Rule::unique('roles', 'name')->where(fn ($query) => $query->where('guard_name', 'web')),
                ],
                'description' => ['nullable', 'string'],
                'is_active' => ['required', 'boolean'],
            ];
        }

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
            'name' => ['required', 'max:255'],
            'email' => in_array($module, ['staff', 'housekeeping'], true)
                ? ['nullable', 'email', 'max:255', 'unique:users,email']
                : ['required', 'email', 'max:255', 'unique:users,email'],
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
        if ($module === 'housekeeping') {
            return $rules + [
                'alternate_country_code' => ['nullable', 'max:10'], 'alternate_phone' => ['nullable', 'max:30'],
                'aadhaar_number' => ['required', 'max:20', 'unique:housekeeping_profiles,aadhaar_number'],
                'pincode' => ['required', 'max:10'], 'address' => ['required', 'max:1000'],
                'employment_type' => ['required', Rule::in(array_keys(HousekeepingProfile::EMPLOYMENT_TYPES))],
                'joining_date' => ['required', 'date'], 'branch_location_id' => ['required'],
                'account_number' => ['required', 'max:50'], 'ifsc_code' => ['required', 'max:20'],
                'emergency_contact_name' => ['required', 'max:255'], 'emergency_country_code' => ['required', 'max:10'],
                'emergency_contact_no' => ['required', 'max:30'], 'medical_fitness_expiry' => ['required', 'date'],
                'police_verification_status' => ['required', Rule::in(array_keys(HousekeepingProfile::VERIFICATION_STATUSES))],
                'verification_status' => ['required', Rule::in(array_keys(HousekeepingProfile::VERIFICATION_STATUSES))],
            ];
        }
        if ($module === 'staff') {
            return $rules + [
                'ref_code' => ['nullable', 'string', 'max:100'],
                'role' => ['required', Rule::in(['Staff', 'Housekeeping', 'Controller', 'Supervisor'])],
                'phone' => ['required', 'max:30', 'unique:users,phone'],
                'employment_type' => ['required', Rule::in(array_keys(StaffProfile::EMPLOYMENT_TYPES))],
                'father_name' => ['required', 'max:255'], 'date_of_birth' => ['required', 'date'],
                'aadhaar_number' => ['required', 'max:20'], 'pan_number' => ['required', 'max:20'],
                'date_of_joining' => ['required', 'date'], 'uan' => ['required', 'max:50'], 'esic_wc' => ['required', 'max:50'],
                'bank_account_number' => ['required', 'max:50'], 'ifsc_code' => ['required', 'max:20'],
                'designation_id' => ['nullable', 'required_if:role,Staff', 'exists:designations,id'],
            ];
        }
        $class = ['controllers' => ControllerProfile::class, 'supervisors' => SupervisorProfile::class][$module];
        $rules += [
            ($module === 'staff' ? 'password' : 'passcode') => $module === 'staff' ? ['required', 'min:8'] : ['required', 'digits:6'],
            'employment_type' => ['required', Rule::in(array_keys($class::EMPLOYMENT_TYPES))], 'father_name' => ['required', 'max:255'],
            'date_of_birth' => ['required', 'date'], 'aadhaar_number' => ['required', 'max:20'], 'pan_number' => ['required', 'max:20'],
            'date_of_joining' => ['required', 'date'], 'uan' => ['required', 'max:50'], 'esic_wc' => ['required', 'max:50'],
            'bank_account_number' => ['required', 'max:50'], 'ifsc_code' => ['required', 'max:20'],
        ];
        if ($module === 'staff') {
            $rules['phone'][] = 'unique:users,phone';
            $rules += [
                'designation_id' => ['required'],
                'reporting_to' => ['nullable', 'integer', 'exists:staff_profiles,user_id'],
                'category' => ['required', Rule::in(array_keys(StaffProfile::CATEGORIES))],
            ];
        }
        return $rules;
    }

    private function readCsv(string $path, array $expected): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) return [[], ['Unable to read the uploaded CSV file.']];
        $header = fgetcsv($handle);
        if (! $header) return [[], ['CSV file is empty.']];
        $header = array_map(fn ($value) => $this->normalizeHeader($value), $header);
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

    private function readSpreadsheet(string $path, array $expected): array
    {
        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $sheet = $reader->load($path)->getActiveSheet();
        } catch (\Throwable) {
            return [[], ['Unable to read the uploaded Excel file.']];
        }

        $highestColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        $header = [];
        for ($column = 1; $column <= $highestColumn; $column++) {
            $header[] = $this->normalizeHeader($sheet->getCell([$column, 1])->getValue());
        }

        $missing = array_diff($expected, $header);
        if ($missing) {
            return [[], ['Missing column(s): ' . implode(', ', $missing) . '.']];
        }

        $rows = [];
        for ($row = 2; $row <= $sheet->getHighestDataRow(); $row++) {
            $values = [];
            for ($column = 1; $column <= $highestColumn; $column++) {
                $values[] = $sheet->getCell([$column, $row])->getFormattedValue();
            }
            if (count(array_filter($values, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }
            $rows[] = ['line' => $row, 'data' => array_combine($header, $values)];
        }

        return $rows ? [$rows, []] : [[], ['Excel file does not contain any data rows.']];
    }

    private function normalizeHeader(mixed $value): string
    {
        return Str::of((string) $value)
            ->trim()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();
    }

    private function splitPhone(string $value): array
    {
        $value = trim($value);
        $countryCode = '+91';
        $phone = $value;

        if (preg_match('/^(\+\d{1,4})[\s-]*(.+)$/', $value, $matches)) {
            $countryCode = $matches[1];
            $phone = $matches[2];
        }

        return [$countryCode, preg_replace('/[^0-9]/', '', $phone) ?: $phone];
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
            'housekeeping' => [
                'label'=>'Housekeeping','permission'=>'housekeeping-management.create','index_route'=>'housekeeping-management.index',
                'headers'=>['name','country_code','phone','alternate_country_code','alternate_phone','email','is_active','aadhaar_number','country','state','district','location','pincode','address','employment_type','joining_date','depot','branch','account_number','ifsc_code','emergency_contact_name','emergency_country_code','emergency_contact_no','medical_fitness_expiry','police_verification_status','verification_status'],
                'sample'=>['Sample Housekeeper','+91','9876543210','','','housekeeper@example.com','yes','123456789012','India','Maharashtra','Pune','Pune','411001','Sample address','permanent','2026-01-01','Central Depot','Main Branch','1234567890','ABCD0001234','Contact Person','+91','9876543211','2027-01-01','verified','verified'],
                'unique_csv'=>['email','aadhaar_number'],
                'profile_fields'=>['alternate_country_code','alternate_phone','aadhaar_number','country','state_id','district_id','location_id','pincode','address','employment_type','joining_date','depot_id','branch_location_id','account_number','ifsc_code','emergency_contact_name','emergency_country_code','emergency_contact_no','medical_fitness_expiry','police_verification_status','verification_status'],
            ],
            'designations' => [
                'label' => 'Designations',
                'permission' => 'designations.create',
                'index_route' => 'designations.index',
                'headers' => ['name', 'department', 'level', 'reporting_to', 'is_active', 'description'],
                'sample' => ['Assistant Manager', 'Operations', 'Level 2', 'Supervisor', 'yes', 'Assists the operations manager.'],
                'unique_csv' => ['name'],
            ],
        ];
        foreach (['controllers'=>['Controller','Controller Management Module','CTL'], 'supervisors'=>['Supervisor','Supervisor Management Module','SUP'], 'staff'=>['Staff','Staff Management Module','STF']] as $key => $meta) {
            $extra = $key === 'staff' ? ['password','designation','reporting_to','category'] : ['passcode'];
            $headers = array_merge(array_slice($commonPerson, 0, 4), $extra, array_slice($commonPerson, 4));
            $sampleExtra = $key === 'staff' ? ['password123','Manager','','skilled'] : ['123456'];
            $configs[$key] = [
                'label'=>$meta[0] . 's','permission'=>Str::singular($key) . '-management.create','index_route'=>Str::singular($key) . '-management.index',
                'headers'=>$headers,
                'sample'=>array_merge(['Sample ' . $meta[0], $key . '@example.com','+91','9876543210'], $sampleExtra, ['Central Depot','full_time','yes','Father Name','1990-01-01','123456789012','ABCDE1234F','2026-01-01','UAN001','ESIC001','India','Maharashtra','Pune','Pune','1234567890','ABCD0001234']),
                'unique_csv'=>['email'], 'profile_fields'=>$key === 'staff' ? array_merge($personProfile, ['designation_id','reporting_to','category']) : $personProfile,
            ];
        }
        $configs['staff'] = [
            'label' => 'Employees', 'permission' => 'staff-management.create', 'index_route' => 'staff-management.index',
            'headers' => [
                'ref_code','name','email','phone','role','designation','depot','employment_type','status',
                'father_name','date_of_birth','aadhaar_number','pan_number','date_of_joining','uan','esic_wc','country','state',
                'district','location','account_number','ifsc_code',
            ],
            'sample_headers' => [
                'Ref Code', 'Name', 'Email', 'Phone', 'Role', 'Designation', 'Depot', 'Employment Type', 'Status',
                'Father Name', 'Date of Birth', 'Aadhaar Number', 'PAN Number', 'Date of Joining', 'UAN', 'ESIC / WC',
                'Country', 'State', 'District', 'Location', 'Account Number', 'IFSC Code',
            ],
            'sample' => [
                'REF-001','Sample Employee','employee@example.com','+91 9876543210','Staff','Manager','Central Depot',
                'full_time','Active','Father Name','1990-01-01','123456789012','ABCDE1234F','2026-01-01','UAN001',
                'ESIC001','India','Maharashtra','Pune','Pune','1234567890','ABCD0001234',
            ],
            'unique_csv' => ['email', 'phone'],
        ];
        abort_unless(isset($configs[$module]), 404);
        $optional = match ($module) {
            'vehicles' => ['variant', 'capacity_seating', 'capacity_load', 'battery_capacity', 'range_km', 'engine_no', 'registration_date', 'registration_valid_upto', 'fitness_expiry', 'permit_expiry', 'insurance_expiry', 'pollution_expiry', 'gps_imei', 'remarks'],
            'drivers' => ['alternate_country_code', 'alternate_phone', 'badge_number', 'badge_expiry_date'],
            'housekeeping' => ['alternate_country_code', 'alternate_phone'],
            'staff' => ['email', 'ref_code'],
            'designations' => ['reporting_to', 'description'],
            default => [],
        };
        $configs[$module]['instructions'] = collect($configs[$module]['headers'])
            ->map(fn (string $column, int $index) => [
                'column' => $configs[$module]['sample_headers'][$index] ?? $column,
                'required' => in_array($column, ['battery_capacity', 'range_km', 'gps_imei'], true)
                    ? 'Conditional'
                    : ($module === 'staff' && $column === 'designation'
                        ? 'Staff only'
                    : (in_array($column, $optional, true) ? 'No' : 'Yes')),
                'instruction' => $this->columnInstruction($column, $module),
            ])->all();

        return $configs[$module];
    }

    private function columnInstruction(string $column, string $module): string
    {
        $instructions = [
            'name' => $module === 'designations'
                ? 'Designation title, maximum 255 characters. It must be unique among designations and application roles.'
                : 'Full name, maximum 255 characters.',
            'email' => 'Valid and unique email address. It must not already belong to another user or appear twice in the CSV.',
            'country_code' => 'Telephone country code, for example +91.',
            'phone' => $module === 'staff'
                ? 'Full phone number with country code, for example +91 9876543210. If omitted, +91 is used.'
                : 'Primary phone number, maximum 30 characters.',
            'ref_code' => 'Optional external reference code.',
            'role' => 'Use Staff, Housekeeping, Controller, or Supervisor.',
            'alternate_country_code' => 'Optional alternate telephone country code, for example +91.',
            'alternate_phone' => 'Optional alternate phone number, maximum 30 characters.',
            'passcode' => 'Exactly 6 digits. This becomes the initial login passcode.',
            'password' => 'Initial login password containing at least 8 characters.',
            'is_active' => 'Use yes/no, true/false, active/inactive, or 1/0.',
            'status' => 'Use Active or Inactive. yes/no, true/false, and 1/0 are also accepted.',
            'father_name' => 'Father name, maximum 255 characters.',
            'date_of_birth' => 'Use YYYY-MM-DD.',
            'aadhaar_number' => in_array($module, ['drivers', 'housekeeping'], true) ? 'Aadhaar number, maximum 20 characters; must be unique.' : 'Aadhaar number, maximum 20 characters.',
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
            'department' => 'Exact existing department name. Do not use an ID. The name must identify one department.',
            'level' => 'Exact existing level name. Do not use an ID. The name must identify one level.',
            'reporting_to' => $module === 'staff'
                ? 'Optional. Use the exact existing staff name. Do not use an ID; the name must identify one staff member.'
                : 'Optional. Use the exact existing role name: Staff, Driver, Controller, or Supervisor. Do not use an ID.',
            'description' => 'Optional description of the designation and its responsibilities.',
            'employment_type' => in_array($module, ['drivers', 'housekeeping'], true) ? 'Use permanent or contract.' : 'Use full_time, part_time, or contract.',
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
