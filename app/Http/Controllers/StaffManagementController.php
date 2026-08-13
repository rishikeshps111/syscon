<?php

namespace App\Http\Controllers;

use App\Exports\StaffManagementExport;
use App\Http\Requests\SaveUnifiedStaffRequest;
use App\Models\BranchLocation;
use App\Models\ControllerProfile;
use App\Models\Designation;
use App\Models\Depot;
use App\Models\District;
use App\Models\Location;
use App\Models\HousekeepingProfile;
use App\Models\StaffProfile;
use App\Models\State;
use App\Models\SupervisorProfile;
use App\Models\User;
use App\Support\SalaryComponents;
use App\Support\UserCodeGenerator;
use App\Support\StaffReportingManagers;
use App\Support\EmployeeActivationGuard;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class StaffManagementController extends Controller implements HasMiddleware
{
    private const ROLES = ['Staff', 'Housekeeping', 'Controller', 'Supervisor'];

    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('staff-management.view|housekeeping-management.view|controller-management.view|supervisor-management.view'), ['index', 'show', 'export', 'downloadPdf', 'statesByCountry', 'districtsByState', 'locationsByDistrict', 'reportingManagers', 'salaryStructure']),
            new Middleware(PermissionMiddleware::using('staff-management.create|housekeeping-management.create|controller-management.create|supervisor-management.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('staff-management.edit|housekeeping-management.edit|controller-management.edit|supervisor-management.edit'), ['edit', 'update', 'status']),
            new Middleware(PermissionMiddleware::using('staff-management.delete|housekeeping-management.delete|controller-management.delete|supervisor-management.delete'), ['destroy']),
        ];
    }

    public function index()
    {
        if (request()->ajax()) {
            return DataTables::of($this->filteredQuery())
                ->addIndexColumn()
                ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')
                ->addColumn('role', fn(User $row) => $this->employeeRole($row))
                ->addColumn('designation', fn($row) => $row->staffProfile?->designation?->name ?? '-')
                ->addColumn('date_of_joining', fn(User $row) => $this->employeeDateOfJoining($row)?->format('d M y') ?? '-')
                ->addColumn('status', function ($row) {
                    return $row->is_active
                        ? '<span class="status-green">Active</span>'
                        : '<span class="status-red">Inactive</span>';
                })
                ->addColumn('action', fn($row) => view('staff-management.partials.action', ['row' => $row, 'role' => $this->employeeRole($row)])->render())
                ->rawColumns(['checkbox', 'status', 'action'])
                ->make(true);
        }

        return view('staff-management.index', $this->formData());
    }

    public function create()
    {
        $defaultState = State::where('is_active', true)->where('is_default', true)->first();
        $defaultDistrict = $defaultState
            ? District::where('state_id', $defaultState->id)->where('is_active', true)->where('is_default', true)->first()
            : null;
        $defaultLocation = $defaultDistrict
            ? Location::where('state_id', $defaultState->id)->where('district_id', $defaultDistrict->id)->where('is_active', true)->where('is_default', true)->first()
            : null;

        return view('staff-management.unified-form', array_merge($this->formData(), [
            'districts' => $defaultState ? District::where('state_id', $defaultState->id)->where('is_active', true)->orderBy('name')->get(['id', 'name']) : collect(),
            'locations' => $defaultDistrict ? Location::where('state_id', $defaultState->id)->where('district_id', $defaultDistrict->id)->where('is_active', true)->orderBy('name')->get(['id', 'name']) : collect(),
            'defaultStateId' => $defaultState?->id,
            'defaultDistrictId' => $defaultDistrict?->id,
            'defaultLocationId' => $defaultLocation?->id,
        ]));
    }

    public function store(SaveUnifiedStaffRequest $request)
    {
        $data = $request->validated();
        DB::transaction(function () use ($request, $data): void {
            $role = $data['role'];
            $credential = $role === 'Staff' ? ($data['password'] ?? null) : ($data['passcode'] ?? null);
            $user = User::create([
                'code' => null,
                'ref_code' => $data['ref_code'] ?? null,
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'country_code' => $data['country_code'],
                'phone' => $data['phone'],
                'password' => $credential ?: Str::random(40),
                'is_active' => $data['is_active'],
            ]);
            $user->update(['code' => UserCodeGenerator::generate($role, (int) $data['depot_id'], $user->id)]);
            $this->storeAvatar($request, $user);
            $this->saveEmployeeProfile($user, $role, $data);
            SalaryComponents::sync($user, $data['salary_components'] ?? []);
            $this->syncEmployeeRoles($user, $role, $data['designation_id'] ?? null);
        });

        return redirect()->route('staff-management.index')->with('success', 'Employee created successfully.');
    }

    public function show(User $staff_management)
    {
        abort_unless($staff_management->hasRole('Staff'), 404);

        $record = $this->staffRecord($staff_management);

        return view('staff-management.show', compact('record'));
    }

    public function downloadPdf(User $staff_management)
    {
        abort_unless($staff_management->hasRole('Staff'), 404);

        $record = $this->staffRecord($staff_management);
        $pdf = $this->buildStaffPdf($record);
        $fileName = ($record->code ?: 'staff') . '-profile.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function edit(User $staff_management)
    {
        $record = $staff_management->load(['roles', 'staffProfile', 'housekeepingProfile', 'controllerProfile', 'supervisorProfile']);
        $profile = $this->employeeProfile($record);
        $districts = $profile?->state_id
            ? District::where('state_id', $profile->state_id)->orderBy('name')->get(['id', 'name'])
            : collect();
        $locations = $profile?->state_id && $profile?->district_id
            ? Location::where('state_id', $profile->state_id)->where('district_id', $profile->district_id)->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('staff-management.unified-form', array_merge($this->formData(), [
            'employeeRole' => $this->employeeRole($record),
        ], compact('record', 'profile', 'districts', 'locations')));
    }

    public function update(SaveUnifiedStaffRequest $request, User $staff_management)
    {
        $data = $request->validated();
        $previousRole = $this->employeeRole($staff_management);
        $role = $data['role'];
        DB::transaction(function () use ($request, $data, $role, $previousRole, $staff_management): void {
            $credential = $role === 'Staff' ? ($data['password'] ?? null) : ($data['passcode'] ?? null);
            $staff_management->update([
                'ref_code' => $data['ref_code'] ?? null,
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'country_code' => $data['country_code'],
                'phone' => $data['phone'],
                'is_active' => $data['is_active'],
            ] + (filled($credential) ? ['password' => $credential] : []));
            $this->storeAvatar($request, $staff_management);
            $this->saveEmployeeProfile($staff_management, $role, $data);
            SalaryComponents::sync($staff_management, $data['salary_components'] ?? []);
            $this->syncEmployeeRoles($staff_management, $role, $data['designation_id'] ?? null);
            if ($previousRole !== $role) {
                $staff_management->update([
                    'code' => UserCodeGenerator::generate($role, (int) $data['depot_id'], $staff_management->id),
                ]);
            }
        });

        return redirect()->route('staff-management.index')->with('success', 'Employee updated successfully.');
    }

    public function destroy(User $staff_management)
    {
        if ($staff_management->avatar) {
            Storage::disk('public')->delete($staff_management->avatar);
        }

        $staff_management->delete();

        return response()->json([
            'success' => true,
            'message' => 'Staff deleted successfully.',
        ]);
    }

    public function export(Request $request)
    {
        $ids = $request->input('ids', []);
        $query = $this->filteredQuery();

        if (! empty($ids)) {
            $query->whereIn('users.id', $ids);
        }

        return Excel::download(new StaffManagementExport($query), 'staff-management.xlsx');
    }

    public function status(Request $request, EmployeeActivationGuard $activationGuard)
    {
        $request->validate([
            'id' => ['required', 'integer', 'exists:users,id'],
            'status' => ['required', 'boolean'],
        ]);

        $staff = User::role(self::ROLES)->findOrFail($request->id);

        if ($request->boolean('status')) {
            $role = $this->employeeRole($staff);
            $missingDocuments = $activationGuard->missingMandatoryDocuments($staff, $role);

            if ($missingDocuments->isNotEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This employee cannot be activated until all mandatory documents are uploaded and verified.',
                    'errors' => [
                        'documents' => ['Missing mandatory documents: ' . $missingDocuments->pluck('name')->implode(', ') . '.'],
                    ],
                    'missing_documents' => $missingDocuments->pluck('name')->values(),
                ], 422);
            }
        }

        $staff->is_active = $request->status;
        $staff->save();

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }

    public function districtsByState(Request $request)
    {
        $request->validate([
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
        ]);

        if (! $request->filled('state_id')) {
            return response()->json([]);
        }

        return response()->json(
            District::where('state_id', $request->state_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
        );
    }

    public function statesByCountry(Request $request)
    {
        $request->validate(['country' => ['required', Rule::in(['India'])]]);

        return response()->json(
            State::where('is_active', true)->orderBy('name')->get(['id', 'name'])
        );
    }

    public function locationsByDistrict(Request $request)
    {
        $request->validate([
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
        ]);

        if (! $request->filled('state_id') || ! $request->filled('district_id')) {
            return response()->json([]);
        }

        return response()->json(
            Location::where('state_id', $request->state_id)
                ->where('district_id', $request->district_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
        );
    }

    public function reportingManagers(Request $request)
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(self::ROLES)],
            'designation_id' => ['nullable', 'integer', 'exists:designations,id'],
            'depot_id' => ['required', 'integer', 'exists:depots,id'],
            'exclude_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $query = match ($data['role']) {
            'Staff' => filled($data['designation_id'] ?? null)
                ? StaffReportingManagers::query((int) $data['designation_id'], (int) $data['depot_id'], $data['exclude_user_id'] ?? null)
                : User::query()->whereRaw('1 = 0'),
            'Controller', 'Housekeeping' => User::role('Supervisor')
                ->where('is_active', true)
                ->whereHas('supervisorProfile', fn($profile) => $profile->where('depot_id', $data['depot_id'])),
            'Supervisor' => User::query()->whereRaw('1 = 0'),
        };

        return response()->json(
            $query->when($data['exclude_user_id'] ?? null, fn($users, $id) => $users->where('users.id', '<>', $id))
                ->orderBy('users.name')
                ->get(['users.id', 'users.code', 'users.name'])
        );
    }

    public function salaryStructure(Request $request)
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(self::ROLES)],
            'designation_id' => ['nullable', 'integer', 'exists:designations,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $user = filled($data['user_id'] ?? null) ? User::find($data['user_id']) : null;

        return view('components.dynamic-salary-structure', [
            'salaryComponents' => SalaryComponents::forRole(
                $data['role'],
                $data['role'] === 'Staff' ? ($data['designation_id'] ?? null) : null,
            ),
            'componentValues' => SalaryComponents::valuesFor($user),
        ]);
    }

    private function filteredQuery()
    {
        $query = User::role(self::ROLES)
            ->with([
                'roles',
                'staffProfile.depot',
                'staffProfile.designation',
                'staffProfile.state',
                'staffProfile.district',
                'staffProfile.location',
                'housekeepingProfile.depot',
                'housekeepingProfile.state',
                'housekeepingProfile.district',
                'housekeepingProfile.location',
                'controllerProfile.depot',
                'controllerProfile.state',
                'controllerProfile.district',
                'controllerProfile.location',
                'supervisorProfile.depot',
                'supervisorProfile.state',
                'supervisorProfile.district',
                'supervisorProfile.location',
            ])
            ->select('users.*');

        if (request()->filled('search_text')) {
            $search = request('search_text');
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('users.code', 'like', '%' . $search . '%')
                    ->orWhere('users.name', 'like', '%' . $search . '%')
                    ->orWhere('users.ref_code', 'like', '%' . $search . '%');
            });
        }

        if (request()->filled('role')) {
            $query->role(request('role'));
        }
        if (request()->filled('designation_id')) {
            $query->whereHas('staffProfile', fn($profile) => $profile->where('designation_id', request('designation_id')));
        }
        if (request()->filled('depot_id')) {
            $query->where(fn($employee) => $employee
                ->whereHas('staffProfile', fn($profile) => $profile->where('depot_id', request('depot_id')))
                ->orWhereHas('housekeepingProfile', fn($profile) => $profile->where('depot_id', request('depot_id')))
                ->orWhereHas('controllerProfile', fn($profile) => $profile->where('depot_id', request('depot_id')))
                ->orWhereHas('supervisorProfile', fn($profile) => $profile->where('depot_id', request('depot_id'))));
        }
        if (request()->filled('employment_type')) {
            $query->where(fn($employee) => $employee
                ->whereHas('staffProfile', fn($profile) => $profile->where('employment_type', request('employment_type')))
                ->orWhereHas('housekeepingProfile', fn($profile) => $profile->where('employment_type', request('employment_type')))
                ->orWhereHas('controllerProfile', fn($profile) => $profile->where('employment_type', request('employment_type')))
                ->orWhereHas('supervisorProfile', fn($profile) => $profile->where('employment_type', request('employment_type'))));
        }

        if (request()->filled('date_of_joining')) {
            $query->where(fn($employee) => $employee
                ->whereHas('staffProfile', fn($profile) => $profile->whereDate('date_of_joining', request('date_of_joining')))
                ->orWhereHas('housekeepingProfile', fn($profile) => $profile->whereDate('joining_date', request('date_of_joining')))
                ->orWhereHas('controllerProfile', fn($profile) => $profile->whereDate('date_of_joining', request('date_of_joining')))
                ->orWhereHas('supervisorProfile', fn($profile) => $profile->whereDate('date_of_joining', request('date_of_joining'))));
        }

        if (request()->filled('status') && in_array(request('status'), ['0', '1'], true)) {
            $query->where('users.is_active', request('status'));
        }

        return $query->orderBy('users.created_at', 'desc');
    }

    private function formData(): array
    {
        return [
            'designations' => Designation::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'depots' => Depot::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'branches' => BranchLocation::orderBy('name')->get(['id', 'name']),
            'employeeRoles' => self::ROLES,
            'categories' => StaffProfile::CATEGORIES,
            'employmentTypes' => StaffProfile::EMPLOYMENT_TYPES,
            'housekeepingEmploymentTypes' => HousekeepingProfile::EMPLOYMENT_TYPES,
            'verificationStatuses' => HousekeepingProfile::VERIFICATION_STATUSES,
            'states' => State::orderBy('name')->get(['id', 'name']),
            'districts' => District::orderBy('name')->get(['id', 'name']),
            'locations' => Location::orderBy('name')->get(['id', 'name']),
            'countries' => ['India'],
            'salaryRanges' => [
                '0-25000' => '0 - 25,000',
                '25001-50000' => '25,001 - 50,000',
                '50001-100000' => '50,001 - 1,00,000',
                '100001-' => 'Above 1,00,000',
            ],
            'salaryComponents' => SalaryComponents::forRole($this->requestEmployeeRole(), request()->route('staff_management')?->staffProfile?->designation_id),
            'salaryComponentValues' => SalaryComponents::valuesFor(request()->route('staff_management')),
        ];
    }

    private function profileData(array $data): array
    {
        $salaryData = SalaryComponents::legacyProfileSalaryData($data['salary_components'] ?? []);

        return collect($data)->only([
            'depot_id',
            'designation_id',
            'reporting_to',
            'category',
            'employment_type',
            'father_name',
            'date_of_birth',
            'aadhaar_number',
            'pan_number',
            'date_of_joining',
            'uan',
            'esic_wc',
            'country',
            'state_id',
            'district_id',
            'location_id',
            'bank_account_number',
            'ifsc_code',
        ])->merge([
            'basic' => $salaryData['basic'],
            'vda' => $salaryData['vda'],
            'basic_vda' => $salaryData['basic_vda'],
            'hra' => $salaryData['hra'],
            'special_allowance' => $salaryData['special_allowance'],
            'conveyance_allowance' => $salaryData['conveyance_allowance'],
            'bonus' => $salaryData['bonus'],
            'gross_salary' => $salaryData['gross_salary'],
        ])->all();
    }

    private function saveEmployeeProfile(User $user, string $role, array $data): void
    {
        $salary = SalaryComponents::legacyProfileSalaryData($data['salary_components'] ?? []);
        $common = collect($data)->only([
            'depot_id',
            'reporting_to',
            'employment_type',
            'father_name',
            'date_of_birth',
            'aadhaar_number',
            'pan_number',
            'date_of_joining',
            'uan',
            'esic_wc',
            'country',
            'state_id',
            'district_id',
            'location_id',
            'bank_account_number',
            'ifsc_code',
        ])->merge(collect($salary)->only([
            'basic',
            'vda',
            'basic_vda',
            'hra',
            'special_allowance',
            'conveyance_allowance',
            'bonus',
            'gross_salary',
        ]))->all();

        if ($role === 'Staff') {
            $user->staffProfile()->updateOrCreate(['user_id' => $user->id], $common + [
                'designation_id' => $data['designation_id'],
                'category' => $data['category'] ?? null,
            ]);
            return;
        }

        if ($role === 'Housekeeping') {
            $housekeeping = $common;
            unset($housekeeping['date_of_joining'], $housekeeping['bank_account_number']);
            $housekeeping += collect($data)->only([
                'branch_location_id',
                'pincode',
                'address',
                'emergency_contact_name',
                'emergency_country_code',
                'emergency_contact_no',
                'medical_fitness_expiry',
                'police_verification_status',
                'verification_status',
            ])->all();
            $housekeeping['joining_date'] = $data['date_of_joining'];
            $housekeeping['account_number'] = $data['bank_account_number'];
            $housekeeping['salary'] = $salary['salary'];
            $user->housekeepingProfile()->updateOrCreate(['user_id' => $user->id], $housekeeping);
            return;
        }

        $relation = $role === 'Controller' ? 'controllerProfile' : 'supervisorProfile';
        $user->{$relation}()->updateOrCreate(['user_id' => $user->id], $common);
    }

    private function syncEmployeeRoles(User $user, string $role, ?int $designationId): void
    {
        $roles = [$role];
        if ($role === 'Staff' && $designationId) {
            $designationRole = Designation::with('role')->find($designationId)?->role?->name;
            if ($designationRole) {
                $roles[] = $designationRole;
            }
        }
        $user->syncRoles($roles);
    }

    private function employeeRole(User $user): string
    {
        return collect(self::ROLES)->first(fn(string $role) => $user->hasRole($role)) ?: 'Staff';
    }

    private function employeeProfile(User $user): mixed
    {
        return match ($this->employeeRole($user)) {
            'Housekeeping' => $user->housekeepingProfile,
            'Controller' => $user->controllerProfile,
            'Supervisor' => $user->supervisorProfile,
            default => $user->staffProfile,
        };
    }

    private function employeeDateOfJoining(User $user): mixed
    {
        $profile = $this->employeeProfile($user);
        return $this->employeeRole($user) === 'Housekeeping' ? $profile?->joining_date : $profile?->date_of_joining;
    }

    private function requestEmployeeRole(): string
    {
        $record = request()->route('staff_management');
        if ($record instanceof User) {
            return $this->employeeRole($record);
        }
        return in_array(request('role'), self::ROLES, true) ? request('role') : 'Staff';
    }

    private function salaryComponent(array $data, string $field): float
    {
        return filled($data[$field] ?? null) ? (float) $data[$field] : 0.0;
    }

    private function nullableSalaryComponent(array $data, string $field): ?float
    {
        return filled($data[$field] ?? null) ? (float) $data[$field] : null;
    }

    private function storeAvatar(Request $request, User $user): void
    {
        if (! $request->hasFile('avatar')) {
            return;
        }

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->avatar = $request->file('avatar')->store('avatars', 'public');
        $user->save();
    }

    private function syncStaffRoles(User $user, int $designationId): void
    {
        $roles = ['Staff'];
        $designation = Designation::with('role')->find($designationId);

        if ($designation?->role) {
            $roles[] = $designation->role->name;
        }

        $user->syncRoles($roles);
    }

    private function staffRecord(User $staff): User
    {
        return $staff->load([
            'roles',
            'staffProfile.designation',
            'staffProfile.reportingTo',
            'staffProfile.depot',
            'staffProfile.state',
            'staffProfile.district',
            'staffProfile.location',
            'staffDocuments.documentType',
        ]);
    }

    private function buildStaffPdf(User $record): string
    {
        $profile = $record->staffProfile;

        $content = '';
        $this->pdfFill($content, 0.96, 0.97, 0.99, 0, 0, 595, 842);
        $this->pdfText($content, 'SYSCON', 50, 795, 18, 'F2');
        $this->pdfText($content, 'Staff Profile', 50, 770, 22, 'F2');
        $this->pdfText($content, 'Generated on ' . now()->format('d-m-Y'), 430, 795, 10);
        $this->pdfStatus($content, $record->is_active ? 'Active' : 'Inactive', 465, 765, $record->is_active);

        $this->pdfCard($content, 40, 600, 515, 140);
        $this->pdfFill($content, 0.90, 0.94, 1.00, 58, 645, 82, 72);
        $this->pdfText($content, 'PHOTO', 82, 678, 11, 'F2');
        $this->pdfText($content, $record->name ?: '-', 160, 708, 18, 'F2');
        $this->pdfText($content, 'Staff Code: ' . ($record->code ?: '-'), 160, 686, 11);
        $this->pdfText($content, 'Email: ' . ($record->email ?: '-'), 160, 668, 10);
        $this->pdfText($content, 'Phone: ' . ($record->full_phone ?: '-'), 160, 650, 10);
        $this->pdfText($content, 'Role: ' . ($record->roles->pluck('name')->implode(', ') ?: 'Staff'), 160, 632, 10);
        $this->pdfText($content, 'Designation: ' . ($profile?->designation?->name ?: '-'), 340, 686, 10);
        $this->pdfText($content, 'DOJ: ' . ($profile?->date_of_joining?->format('d-m-Y') ?: '-'), 340, 668, 10);
        $this->pdfText($content, 'Category: ' . ($profile?->category_label ?: '-'), 340, 650, 10);

        $this->pdfSection($content, 'Personal Details', 40, 470, 250, [
            "Father's Name" => $profile?->father_name ?: '-',
            'Date of Birth' => $profile?->date_of_birth?->format('d-m-Y') ?: '-',
            'Aadhaar Number' => $profile?->aadhaar_number ?: '-',
            'PAN Number' => $profile?->pan_number ?: '-',
        ]);

        $this->pdfSection($content, 'Location Details', 305, 470, 250, [
            'Country' => $profile?->country ?: '-',
            'State' => $profile?->state?->name ?: '-',
            'District' => $profile?->district?->name ?: '-',
            'Location' => $profile?->location?->name ?: '-',
        ]);

        $this->pdfSection($content, 'Employment Details', 40, 300, 250, [
            'Employment Type' => $profile?->employment_type_label ?: '-',
            'Reporting To' => $profile?->reportingTo?->name ?: '-',
            'Joining Date' => $profile?->date_of_joining?->format('d-m-Y') ?: '-',
            'UAN' => $profile?->uan ?: '-',
            'ESIC / WC' => $profile?->esic_wc ?: '-',
        ]);

        $this->pdfSection($content, 'Bank Details', 305, 300, 250, [
            'Account Number' => $profile?->bank_account_number ?: '-',
            'IFSC Code' => $profile?->ifsc_code ?: '-',
        ]);

        $money = fn($value) => filled($value) ? number_format((float) $value, 2) : '-';
        $this->pdfSection($content, 'Salary Structure', 40, 105, 515, [
            'Basic' => $money($profile?->basic),
            'VDA' => $money($profile?->vda),
            'Basic + VDA' => $money($profile?->basic_vda),
            'HRA' => $money($profile?->hra),
            'Special Allowance' => $money($profile?->special_allowance),
            'Conveyance Allowance / Incentive' => $money($profile?->conveyance_allowance),
            'Bonus' => $money($profile?->bonus),
            'Gross Salary' => $money($profile?->gross_salary),
        ], 170);

        $pages = [$content];

        $documentContent = '';
        $this->pdfFill($documentContent, 0.96, 0.97, 0.99, 0, 0, 595, 842);
        $this->pdfText($documentContent, 'Staff Documents', 50, 790, 20, 'F2');
        $y = 735;

        if ($record->staffDocuments->isEmpty()) {
            $this->pdfCard($documentContent, 40, 665, 515, 50);
            $this->pdfText($documentContent, 'No documents uploaded.', 60, 692, 11);
        }

        foreach ($record->staffDocuments as $document) {
            if ($y < 90) {
                $pages[] = $documentContent;
                $documentContent = '';
                $this->pdfFill($documentContent, 0.96, 0.97, 0.99, 0, 0, 595, 842);
                $this->pdfText($documentContent, 'Staff Documents', 50, 790, 20, 'F2');
                $y = 735;
            }

            $this->pdfCard($documentContent, 40, $y - 55, 515, 55);
            $this->pdfText($documentContent, $document->documentType?->name ?: 'Document', 60, $y - 22, 12, 'F2');
            $this->pdfText($documentContent, 'Expiry: ' . ($document->expiry_date?->format('d-m-Y') ?: '-'), 60, $y - 40, 10);
            $this->pdfStatus($documentContent, $document->is_verified ? 'Verified' : 'Not Verified', 430, $y - 32, $document->is_verified);
            $y -= 70;
        }

        $pages[] = $documentContent;

        return $this->pdfDocument($pages);
    }

    private function pdfDocument(array $contents): string
    {
        $pageCount = count($contents);
        $fontObject = 3 + ($pageCount * 2);
        $boldFontObject = $fontObject + 1;
        $objects = [];
        $pageObjectNumbers = [];

        $objects[1] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        foreach ($contents as $index => $content) {
            $pageObject = 3 + ($index * 2);
            $contentObject = $pageObject + 1;
            $pageObjectNumbers[] = $pageObject . ' 0 R';
            $objects[$pageObject] = $pageObject . " 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 " . $fontObject . " 0 R /F2 " . $boldFontObject . " 0 R >> >> /Contents " . $contentObject . " 0 R >>\nendobj\n";
            $objects[$contentObject] = $contentObject . " 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream\nendobj\n";
        }

        $objects[2] = "2 0 obj\n<< /Type /Pages /Kids [" . implode(' ', $pageObjectNumbers) . '] /Count ' . count($pageObjectNumbers) . " >>\nendobj\n";
        $objects[$fontObject] = $fontObject . " 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
        $objects[$boldFontObject] = $boldFontObject . " 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>\nendobj\n";
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $objectNumber => $object) {
            $offsets[$objectNumber] = strlen($pdf);
            $pdf .= $object;
        }

        ksort($offsets);
        $xref = strlen($pdf);
        $maxObject = max(array_keys($offsets));
        $pdf .= "xref\n0 " . ($maxObject + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= $maxObject; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }

        $pdf .= "trailer\n<< /Size " . ($maxObject + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xref . "\n%%EOF";

        return $pdf;
    }

    private function pdfSection(string &$content, string $title, int $x, int $y, int $width, array $items, int $height = 140): void
    {
        $this->pdfCard($content, $x, $y, $width, $height);
        $this->pdfText($content, $title, $x + 14, $y + $height - 26, 13, 'F2');
        $lineY = $y + $height - 50;

        foreach ($items as $label => $value) {
            $this->pdfText($content, $label . ':', $x + 14, $lineY, 9, 'F2');
            $this->pdfText($content, (string) $value, $x + 150, $lineY, 9);
            $lineY -= 17;
        }
    }

    private function pdfCard(string &$content, int $x, int $y, int $width, int $height): void
    {
        $this->pdfFill($content, 1, 1, 1, $x, $y, $width, $height);
        $content .= "0.84 0.86 0.90 RG\n";
        $content .= $x . ' ' . $y . ' ' . $width . ' ' . $height . " re S\n";
    }

    private function pdfFill(string &$content, float $r, float $g, float $b, int $x, int $y, int $width, int $height): void
    {
        $content .= sprintf("%.2f %.2f %.2f rg\n%d %d %d %d re f\n", $r, $g, $b, $x, $y, $width, $height);
    }

    private function pdfText(string &$content, string $text, int $x, int $y, int $size = 10, string $font = 'F1'): void
    {
        $content .= "0.08 0.10 0.14 rg\n";
        $content .= "BT\n/" . $font . ' ' . $size . " Tf\n" . $x . ' ' . $y . " Td\n(" . $this->escapePdfText(substr($text, 0, 78)) . ") Tj\nET\n";
    }

    private function pdfStatus(string &$content, string $text, int $x, int $y, bool $positive): void
    {
        if ($positive) {
            $this->pdfFill($content, 0.88, 0.97, 0.91, $x, $y, 82, 24);
            $content .= "0.13 0.55 0.27 rg\n";
        } else {
            $this->pdfFill($content, 1.00, 0.90, 0.90, $x, $y, 82, 24);
            $content .= "0.78 0.16 0.16 rg\n";
        }

        $content .= "BT\n/F2 10 Tf\n" . ($x + 14) . ' ' . ($y + 8) . " Td\n(" . $this->escapePdfText($text) . ") Tj\nET\n";
    }

    private function escapePdfText(string $text): string
    {
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
