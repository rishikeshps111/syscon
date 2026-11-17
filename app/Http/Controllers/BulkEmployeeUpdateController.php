<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveUnifiedStaffRequest;
use App\Http\Requests\UpdateDriverManagementRequest;
use App\Http\Requests\UpdateHousekeepingManagementRequest;
use App\Models\BranchLocation;
use App\Models\Depot;
use App\Models\Designation;
use App\Models\District;
use App\Models\Location;
use App\Models\State;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class BulkEmployeeUpdateController extends BulkImportController
{
    private const MODULES = [
        'staff' => ['Staff', 'staff'], 'drivers' => ['Driver', 'driver'],
        'housekeeping' => ['Housekeeping', 'housekeeping'],
        'controllers' => ['Controller', 'controller'], 'supervisors' => ['Supervisor', 'supervisor'],
    ];

    private const COMMON = [
        'name', 'email', 'country_code', 'phone', 'is_active', 'designation', 'depot',
        'employment_type', 'date_of_birth', 'aadhaar_number', 'pan_number', 'uan',
        'country', 'state', 'district', 'location', 'account_number', 'ifsc_code',
    ];

    private const OFFICE = ['father_name', 'date_of_joining', 'esic_wc', 'reporting_to'];

    private const OPERATIONS = [
        'alternate_country_code', 'alternate_phone', 'pincode', 'address', 'joining_date', 'branch',
        'emergency_contact_name', 'emergency_country_code', 'emergency_contact_no',
        'medical_fitness_expiry', 'police_verification_status', 'verification_status',
    ];

    private const DRIVER = ['license_number', 'license_type', 'issue_date', 'expiry_date', 'badge_number', 'badge_expiry_date', 'wc_policy'];

    private function rolesFor(string $module): array
    {
        abort_unless(isset(self::MODULES[$module]), 404);
        $modules = $module === 'staff' ? ['staff', 'housekeeping', 'controllers', 'supervisors'] : [$module];
        $roles = [];
        foreach ($modules as $key) {
            [$role, $prefix] = self::MODULES[$key];
            if (auth()->user()?->can($prefix.'-management.edit')) {
                $roles[] = $role;
            }
        }
        abort_if($roles === [], 403);
        return $roles;
    }

    private function employees(array $roles)
    {
        return User::query()->where(function ($query) use ($roles) {
            foreach ($roles as $role) {
                $query->orWhere(fn ($q) => $q->role($role)->whereHas(lcfirst($role).'Profile'));
            }
        });
    }

    private function roleFor(User $user, array $roles): string
    {
        $matches = array_values(array_filter($roles, fn ($role) => $user->hasRole($role) && $user->{lcfirst($role).'Profile'}));
        if (count($matches) !== 1) {
            throw ValidationException::withMessages(['csv_file' => 'Employee profile is ambiguous; resolve it before bulk updating.']);
        }
        return $matches[0];
    }

    private function columns(string $role): array
    {
        return array_values(array_unique(array_merge(self::COMMON,
            $role === 'Driver' ? self::DRIVER : self::OFFICE,
            in_array($role, ['Driver', 'Housekeeping'], true)
                ? ($role === 'Housekeeping' ? array_diff(self::OPERATIONS, ['joining_date']) : self::OPERATIONS) : [],
            $role === 'Staff' ? ['category'] : [])));
    }

    private function settings(string $module, array $roles): array
    {
        $headers = ['ref_code', 'name'];
        foreach ($roles as $role) $headers = array_merge($headers, $this->columns($role));
        $headers = array_values(array_unique($headers));
        return [
            'label' => $module === 'staff' ? 'Employees' : ucfirst($module),
            'index_route' => self::MODULES[$module][1].'-management.index',
            'headers' => $headers,
            'instructions' => array_map(fn ($field) => [
                'column' => $field, 'required' => $field === 'ref_code' ? 'Yes' : 'No',
                'instruction' => match ($field) {
                    'ref_code' => 'Existing employee reference code. Used only to match the employee; cannot be changed here.',
                    'name' => 'Current name is included in the sample. Edit it only when the employee name should change.',
                    'reporting_to' => 'Reference code of an eligible reporting manager.',
                    'designation', 'depot', 'state', 'district', 'location', 'branch' => 'Exact existing name, not a database ID.',
                    default => 'Optional update. Leave blank or remove the column to preserve the current value. Role-specific fields apply only to that employee type.',
                },
            ], $headers),
        ];
    }

    public function form(string $module)
    {
        $config = $this->settings($module, $this->rolesFor($module));
        return view('bulk-import.form', ['module' => $module, 'config' => $config, 'updating' => true]);
    }

    public function sample(string $module)
    {
        $roles = $this->rolesFor($module);
        $config = $this->settings($module, $roles);
        $input = request()->validate([
            'sample_scope' => ['sometimes', Rule::in(['all', 'selected'])],
            'user_ids' => ['required_if:sample_scope,selected', 'array', 'min:1'],
            'user_ids.*' => ['required', 'integer', 'distinct'],
        ]);
        $query = $this->employees($roles);
        if (($input['sample_scope'] ?? 'all') === 'selected') {
            $ids = $input['user_ids'];
            $query->whereIn('users.id', $ids);
            if ($query->count() !== count($ids)) {
                throw ValidationException::withMessages(['user_ids' => 'Select employees you can edit in this module.']);
            }
        }
        return response()->streamDownload(function () use ($query, $config) {
            $book = new Spreadsheet;
            try {
                $sheet = $book->getActiveSheet()->setTitle('Employee Bulk Update');
                $sheet->fromArray(array_map(fn ($field) => Str::headline($field), $config['headers']), null, 'A1');
                $line = 2;
                foreach ($query->orderBy('name')->orderBy('id')->cursor() as $user) {
                    $sheet->setCellValueExplicit('A'.$line, (string) $user->ref_code, DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit('B'.$line, $user->name, DataType::TYPE_STRING);
                    $line++;
                }
                $last = Coordinate::stringFromColumnIndex(count($config['headers']));
                $sheet->getStyle('A1:'.$last.'1')->getFont()->setBold(true);
                $sheet->getStyle('A2:'.$last.max(2, $line - 1))->getNumberFormat()->setFormatCode('@');
                foreach (range(1, count($config['headers'])) as $index) $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index))->setWidth(24);
                $sheet->freezePane('C2');
                IOFactory::createWriter($book, 'Xlsx')->save('php://output');
            } finally {
                $book->disconnectWorksheets();
            }
        }, $module.'-bulk-update-sample.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function users(Request $request, string $module)
    {
        $roles = $this->rolesFor($module);
        $input = $request->validate(['q' => ['nullable', 'string', 'max:255'], 'page' => ['nullable', 'integer', 'min:1']]);
        $query = $this->employees($roles);
        if (($term = trim($input['q'] ?? '')) !== '') {
            $query->where(function ($q) use ($term) {
                foreach (['name', 'code', 'ref_code'] as $field) $q->orWhere('users.'.$field, 'like', '%'.$term.'%');
            });
        }
        $users = $query->select(['users.id', 'users.name', 'users.code', 'users.ref_code'])
            ->orderBy('users.name')->orderBy('users.id')->simplePaginate(30);
        return response()->json([
            'results' => $users->getCollection()->map(fn ($user) => [
                'id' => $user->id,
                'text' => $user->name.' | Code: '.($user->code ?: '—').' | Ref: '.($user->ref_code ?: '—'),
            ])->values(),
            'pagination' => ['more' => $users->hasMorePages()],
        ]);
    }

    public function import(Request $request, string $module)
    {
        $roles = $this->rolesFor($module);
        $request->validate(['import_step' => ['required', Rule::in(['prepare', 'validate', 'save'])]]);
        if ($request->input('import_step') === 'prepare') {
            $request->validate(['csv_file' => ['required', 'file', 'mimes:xlsx,xls', 'extensions:xlsx,xls', 'max:5120']]);
            [$rows, $errors] = $this->readSpreadsheet($request->file('csv_file')->getRealPath(), ['ref_code'], true);
            if ($errors) throw ValidationException::withMessages(['csv_file' => $errors]);
            $allowed = $this->settings($module, $roles)['headers'];
            foreach (array_keys($rows[0]['data']) as $header) {
                if (! in_array($header, $allowed, true)) $errors[] = "Unsupported update column: {$header}.";
            }
            if ($errors) throw ValidationException::withMessages(['csv_file' => $errors]);
            $token = (string) Str::uuid();
            $state = ['module' => $module, 'user_id' => $request->user()->id, 'rows' => $rows,
                'offset' => 0, 'saved' => 0, 'updated' => 0, 'unchanged' => 0, 'seen' => [], 'unique' => [],
                'phase' => 'validate', 'expires' => time() + 3600];
            $request->session()->put('bulk_update.'.$token, $state);
            return response()->json($this->progress($state) + ['token' => $token]);
        }
        $request->validate(['token' => ['required', 'uuid'], 'offset' => ['required', 'integer', 'min:0']]);
        $key = 'bulk_update.'.$request->input('token');
        $state = $request->session()->get($key);
        abort_unless($state && $state['module'] === $module && $state['user_id'] === $request->user()->id && $state['expires'] > time(), 410, 'Bulk update expired. Please upload again.');
        $offset = $state['phase'] === 'validate' ? $state['offset'] : $state['saved'];
        abort_unless($request->input('import_step') === $state['phase'] && (int) $request->input('offset') === $offset, 409, 'Bulk update progress changed. Please reload before retrying.');
        $batch = array_slice($state['rows'], $offset, 25);
        if ($state['phase'] === 'validate') {
            foreach ($batch as $row) {
                $this->validatedPatch($row, $roles, $state);
                $state['offset']++;
            }
            if ($state['offset'] === count($state['rows'])) $state['phase'] = 'save';
        } else {
            DB::transaction(function () use ($batch, $roles, &$state) {
                foreach ($batch as $row) {
                    $fresh = ['targets' => $state['targets'] ?? []];
                    [$user, $profile, $userData, $profileData] = $this->validatedPatch($row, $roles, $fresh, true);
                    $user->fill($userData);
                    $profile->fill($profileData);
                    $changed = $user->isDirty() || $profile->isDirty();
                    if ($user->isDirty()) $user->save();
                    if ($profile->isDirty()) $profile->save();
                    $state[$changed ? 'updated' : 'unchanged']++;
                    $state['saved']++;
                }
            });
            if ($state['saved'] === count($state['rows'])) {
                $request->session()->forget($key);
                $message = "Bulk update completed: {$state['updated']} rows updated, {$state['unchanged']} rows unchanged.";
                $request->session()->flash('success', $message);
                return response()->json(array_merge($this->progress($state), ['phase' => 'done', 'message' => $message,
                    'redirect' => route($this->settings($module, $roles)['index_route'])]));
            }
        }
        $request->session()->put($key, $state);
        return response()->json($this->progress($state));
    }

    private function progress(array $state): array
    {
        return ['phase' => $state['phase'], 'processed' => $state['phase'] === 'validate' ? $state['offset'] : $state['saved'],
            'total' => count($state['rows']), 'validated' => $state['offset'], 'saved' => $state['saved'],
            'created' => 0, 'updated' => $state['updated'], 'unchanged' => $state['unchanged']];
    }

    private function validatedPatch(array $row, array $roles, array &$state, bool $lock = false): array
    {
        $line = $row['line'];
        $fail = fn ($message) => throw ValidationException::withMessages(['csv_file' => "Row {$line}: {$message}"]);
        $data = array_filter(array_map(fn ($value) => trim((string) $value), $row['data']), fn ($value) => $value !== '');
        $ref = $data['ref_code'] ?? '';
        if ($ref === '' || mb_strlen($ref) > 100) $fail('Ref Code is required and must not exceed 100 characters.');
        $users = $this->employees($roles)->where('ref_code', $ref)->when($lock, fn ($q) => $q->lockForUpdate())->limit(2)->get();
        if ($users->count() !== 1) $fail($users->isEmpty() ? "Ref Code {$ref} was not found in this module." : "Ref Code {$ref} is ambiguous.");
        $user = $users->first();
        if (isset($state['seen'][$user->id])) $fail('Duplicate Ref Code; first seen on row '.$state['seen'][$user->id].'.');
        $state['seen'][$user->id] = $line;
        $role = $this->roleFor($user, $roles);
        $relation = lcfirst($role).'Profile';
        $profile = $user->{$relation}()->when($lock, fn ($q) => $q->lockForUpdate())->firstOrFail();
        $identity = [$user->id, $role, $profile->id];
        if (isset($state['targets'][$line]) && $state['targets'][$line] !== $identity) $fail('Employee matching changed. Upload and validate the file again.');
        $state['targets'][$line] = $identity;
        unset($data['ref_code']);
        foreach (array_keys($data) as $field) if (! in_array($field, $this->columns($role), true)) $fail("{$field} cannot be updated for {$role} employees.");
        $map = ['designation' => 'designation_id', 'depot' => 'depot_id', 'state' => 'state_id', 'district' => 'district_id', 'location' => 'location_id', 'branch' => 'branch_location_id'];
        $models = ['designation' => Designation::class, 'depot' => Depot::class, 'state' => State::class, 'district' => District::class, 'location' => Location::class, 'branch' => BranchLocation::class];
        $context = $profile->getAttributes();
        foreach ($map as $field => $target) {
            if (! isset($data[$field])) continue;
            $query = $models[$field]::query()->whereRaw('LOWER(name) = ?', [Str::lower($data[$field])]);
            if ($field === 'designation') $query->where('role_type', $role)->where('is_active', true);
            if (in_array($field, ['district', 'location'], true)) $query->where('state_id', $context['state_id'] ?? null);
            if ($field === 'location') $query->where('district_id', $context['district_id'] ?? null);
            $matches = $query->limit(2)->get();
            if ($matches->count() !== 1) $fail("{$field} '{$data[$field]}' was not found or is ambiguous.");
            $data[$target] = $context[$target] = $matches->first()->id;
            unset($data[$field]);
        }
        if (isset($data['reporting_to'])) {
            $matches = User::where('ref_code', $data['reporting_to'])->limit(2)->get();
            if ($matches->count() !== 1) $fail('Reporting manager Ref Code was not found or is ambiguous.');
            $data['reporting_to'] = $matches->first()->id;
        }
        foreach ($data as $field => &$value) {
            if (in_array($field, ['date_of_birth', 'date_of_joining', 'joining_date', 'issue_date', 'expiry_date', 'badge_expiry_date', 'medical_fitness_expiry'], true)) {
                $parsed = $this->parseImportDate($value);
                if (! $parsed) $fail("{$field} must be a valid date in dd-mm-yyyy format.");
                $value = $parsed->format('Y-m-d');
            }
            if ($field === 'is_active') $value = $this->booleanValue($value);
        }
        unset($value);
        $operations = in_array($role, ['Driver', 'Housekeeping'], true);
        if (! $operations && isset($data['account_number'])) {
            $data['bank_account_number'] = $data['account_number']; unset($data['account_number']);
        }
        if ($role === 'Housekeeping' && isset($data['date_of_joining'])) {
            $data['joining_date'] = $data['date_of_joining']; unset($data['date_of_joining']);
        }
        $context = array_merge($context, $user->only(['name', 'email', 'phone', 'country_code', 'is_active']), $data, ['role' => $role]);
        if ($role === 'Housekeeping') {
            $context['date_of_joining'] = $context['joining_date'] ?? null;
            $context['bank_account_number'] = $context['account_number'] ?? null;
        }
        $form = $role === 'Driver' ? new UpdateDriverManagementRequest : new SaveUnifiedStaffRequest;
        $form->replace($context);
        $route = (new Route('PUT', '/', []))->bind(Request::create('/', 'PUT'));
        $route->setParameter($role === 'Driver' ? 'driver_management' : 'staff_management', $user);
        $form->setRouteResolver(fn () => $route);
        $rules = $form->rules();
        if ($role === 'Housekeeping') {
            $extra = new UpdateHousekeepingManagementRequest;
            $extra->replace($context);
            $route->setParameter('housekeeping_management', $user);
            $extra->setRouteResolver(fn () => $route);
            $rules = array_replace($rules, $extra->rules());
        }
        $fields = array_keys($data);
        if (array_intersect($fields, ['state_id', 'district_id'])) $fields = array_merge($fields, ['district_id', 'location_id']);
        if (in_array('issue_date', $fields, true)) $fields = array_merge($fields, ['expiry_date', 'badge_expiry_date']);
        if ($role !== 'Driver' && array_intersect($fields, ['depot_id', 'designation_id'])) $fields[] = 'reporting_to';
        $validator = Validator::make($context, array_intersect_key($rules, array_flip($fields)));
        if ($validator->fails()) $fail(implode(' ', $validator->errors()->all()));
        foreach (['email', 'phone', 'aadhaar_number', 'license_number'] as $field) {
            if (! isset($data[$field])) continue;
            $unique = $field === 'email' || ($field === 'phone' && $role !== 'Driver') || ($field === 'aadhaar_number' && $operations) || $field === 'license_number';
            if (! $unique) continue;
            $key = ($field === 'aadhaar_number' ? $role : '').'|'.$field.'|'.Str::lower((string) $data[$field]);
            if (isset($state['unique'][$key])) $fail("Duplicate {$field}; first seen on row {$state['unique'][$key]}.");
            $state['unique'][$key] = $line;
        }
        $userData = array_intersect_key($data, array_flip(['name', 'email', 'country_code', 'phone', 'is_active']));
        return [$user, $profile, $userData, array_diff_key($data, $userData)];
    }
}
