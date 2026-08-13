<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Depot;
use App\Models\Leave;
use App\Models\SalaryProcessing;
use App\Models\SalaryProcessingItem;
use App\Models\TripSheetEntryDor;
use App\Models\User;
use App\Support\SalaryComponents;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class SalaryProcessingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('salary-processing.view'), ['index', 'show', 'users']),
            new Middleware(PermissionMiddleware::using('salary-processing.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('salary-processing.edit'), ['edit', 'update']),
            new Middleware(PermissionMiddleware::using('salary-processing.delete'), ['destroy']),
            new Middleware(PermissionMiddleware::using('salary-processing.approve'), ['approve']),
        ];
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = SalaryProcessing::with(['depot', 'role', 'creator', 'approver'])
                ->withCount('items')
                ->latest();

            foreach (['year', 'month', 'depot_id', 'role_id'] as $field) {
                if ($request->filled($field)) {
                    $query->where($field, $request->input($field));
                }
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="row-check" value="' . $row->id . '">')
                ->addColumn('month_name', fn($row) => Carbon::create($row->year, $row->month, 1)->format('F'))
                ->addColumn('depot_name', fn($row) => $row->depot?->name ?? '-')
                ->addColumn('role_name', fn($row) => $row->role?->name ?? '-')
                ->addColumn('created_by_name', fn($row) => $row->creator?->name ?? '-')
                ->addColumn('created_date_time', fn($row) => $row->created_at?->format('d-m-Y h:i A') ?? '-')
                ->addColumn('approved_by_name', fn($row) => $row->approver ? $row->approver->name . '<br><small>' . $row->approved_at?->format('d-m-Y h:i A') . '</small>' : '-')
                ->addColumn('approval_status_label', fn($row) => $this->approvalStatusBadge($row))
                ->addColumn('action', fn($row) => view('salary-processing.partials.action', compact('row'))->render())
                ->rawColumns(['checkbox', 'approved_by_name', 'approval_status_label', 'action'])
                ->make(true);
        }

        return view('salary-processing.index', $this->commonData());
    }

    public function create(Request $request)
    {
        $filters = $this->validatedFilters($request, false);
        $rows = $this->salaryRows($filters);

        return view('salary-processing.form', $this->commonData() + [
            'record' => null,
            'rows' => $rows,
            'filters' => $filters,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedPayload($request);

        DB::transaction(function () use ($data) {
            $processing = SalaryProcessing::updateOrCreate(
                collect($data)->only(['year', 'month', 'depot_id', 'role_id'])->all(),
                collect($data)->only(['salary_date', 'payment_method', 'remarks'])->merge([
                    'status' => 'Completed',
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                    'approved_by' => null,
                    'approved_at' => null,
                ])->all()
            );

            $this->syncItems($processing, $data);
        });

        return redirect()->route('salary-processing.index')->with('success', 'Salary processing saved successfully.');
    }

    public function edit(SalaryProcessing $salaryProcessing)
    {
        $salaryProcessing->load(['items.user', 'items.salaryProcessing.role', 'depot', 'role']);
        $rows = $salaryProcessing->items->map(fn($item) => $this->storedRow($item))->values();

        return view('salary-processing.form', $this->commonData() + [
            'record' => $salaryProcessing,
            'rows' => $rows,
            'filters' => [
                'year' => $salaryProcessing->year,
                'month' => $salaryProcessing->month,
                'depot_id' => $salaryProcessing->depot_id,
                'role_id' => $salaryProcessing->role_id,
            ],
        ]);
    }

    public function update(Request $request, SalaryProcessing $salaryProcessing)
    {
        $data = $this->validatedPayload($request);

        DB::transaction(function () use ($data, $salaryProcessing) {
            $salaryProcessing->update(collect($data)->only(['salary_date', 'payment_method', 'remarks'])->merge([
                'year' => $data['year'],
                'month' => $data['month'],
                'depot_id' => $data['depot_id'],
                'role_id' => $data['role_id'],
                'status' => $salaryProcessing->status === 'Approved' ? 'Completed' : $salaryProcessing->status,
                'updated_by' => auth()->id(),
                'approved_by' => null,
                'approved_at' => null,
            ])->all());

            $this->syncItems($salaryProcessing, $data);
        });

        return redirect()->route('salary-processing.index')->with('success', 'Salary processing updated successfully.');
    }

    public function destroy(SalaryProcessing $salaryProcessing)
    {
        $salaryProcessing->delete();

        return response()->json(['success' => true, 'message' => 'Salary processing deleted successfully.']);
    }

    public function approve(SalaryProcessing $salaryProcessing)
    {
        $salaryProcessing->update([
            'status' => 'Approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'updated_by' => auth()->id(),
        ]);

        return response()->json(['success' => true, 'message' => 'Salary processing approved successfully.']);
    }

    public function users(Request $request)
    {
        $filters = $this->validatedFilters($request, true);

        return response()->json($this->salaryRows($filters)->values());
    }

    private function validatedFilters(Request $request, bool $requireUserSelection): array
    {
        $rules = [
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'depot_id' => [$requireUserSelection ? 'required' : 'nullable', 'integer', 'exists:depots,id'],
            'role_id' => [$requireUserSelection ? 'required' : 'nullable', 'integer', 'exists:roles,id'],
        ];

        $validated = $request->validate($rules);

        return [
            'year' => (int) ($validated['year'] ?? date('Y')),
            'month' => (int) ($validated['month'] ?? date('n')),
            'depot_id' => isset($validated['depot_id']) ? (int) $validated['depot_id'] : null,
            'role_id' => isset($validated['role_id']) ? (int) $validated['role_id'] : null,
        ];
    }

    private function validatedPayload(Request $request): array
    {
        return $request->validate([
            'year' => ['required', 'integer', 'between:2000,2100'],
            'month' => ['required', 'integer', 'between:1,12'],
            'depot_id' => ['required', 'integer', 'exists:depots,id'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'salary_date' => ['nullable', 'date'],
            'payment_method' => ['required', Rule::in(array_keys(SalaryProcessing::PAYMENT_METHODS))],
            'remarks' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'items.*.deduction' => ['nullable', 'numeric', 'min:0'],
            'items.*.incentive' => ['nullable', 'numeric', 'min:0'],
            'items.*.unauthorized_leaves' => ['nullable', 'numeric', 'min:0'],
            'items.*.selected_components' => ['sometimes', 'array'],
            'items.*.selected_components.*' => ['integer'],
        ]);
    }

    private function syncItems(SalaryProcessing $processing, array $data): void
    {
        $filters = collect($data)->only(['year', 'month', 'depot_id', 'role_id'])->all();
        $baseRows = $this->salaryRows($filters)->keyBy('user_id');
        $keptIds = [];

        foreach ($data['items'] as $item) {
            $userId = (int) $item['user_id'];
            $row = $baseRows->get($userId);

            if (! $row) {
                continue;
            }

            $unauthorizedLeaves = (float) ($item['unauthorized_leaves'] ?? 0);
            $row = $this->applySelectedComponents($row, $item['selected_components'] ?? []);
            $row['deduction'] = array_key_exists('deduction', $item) ? (float) $item['deduction'] : (float) $row['deduction'];
            $row['incentive'] = array_key_exists('incentive', $item) ? (float) $item['incentive'] : (float) $row['incentive'];
            $calculated = $this->applyUnauthorizedLeave($row, $unauthorizedLeaves);

            $salaryItem = SalaryProcessingItem::updateOrCreate(
                ['salary_processing_id' => $processing->id, 'user_id' => $userId],
                collect($calculated)->only([
                    'aadhaar_no',
                    'total_leave_taken',
                    'total_shifts_completed',
                    'total_working_days',
                    'lop',
                    'basic_salary',
                    'deduction',
                    'incentive',
                    'unauthorized_leaves',
                    'net_salary',
                    'salary_split',
                ])->all()
            );
            $keptIds[] = $salaryItem->id;
        }

        $processing->items()->whereNotIn('id', $keptIds)->delete();
    }

    private function salaryRows(array $filters)
    {
        if (empty($filters['depot_id']) || empty($filters['role_id'])) {
            return collect();
        }

        $role = Role::find($filters['role_id']);

        if (! $role) {
            return collect();
        }

        $users = $this->usersForRoleAndDepot($role->name, (int) $filters['depot_id']);
        $start = Carbon::create((int) $filters['year'], (int) $filters['month'], 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $workingDays = $start->daysInMonth;

        return $users->map(function (User $user) use ($role, $start, $end, $workingDays) {
            $split = $this->salarySplit($user, $role->name);
            $earningSplit = collect($split)->where('type', 'earning')->values();
            $incentive = (float) $earningSplit->filter(fn($item) => $this->isIncentiveComponent($item))->sum('amount');
            $grossSalary = $this->grossSalary(
                $user,
                $earningSplit->reject(fn($item) => $this->isIncentiveComponent($item))->all()
            );
            $componentDeduction = (float) collect($split)->where('type', 'deduction')->sum('amount');
            $leaveTaken = $this->leaveTaken($user->id, $start, $end);
            $unpaidLeaveDays = $this->unpaidLeaveTaken($user->id, $start, $end);
            $leaveDeduction = $workingDays > 0
                ? round(((float) $grossSalary / $workingDays) * $unpaidLeaveDays, 2)
                : 0;
            $totalShifts = match ($role->name) {
                'Driver' => $this->completedDriverShifts($user, $start, $end),
                'Housekeeping' => Attendance::where('user_id', $user->id)->whereBetween('attendance_date', [$start, $end])->whereIn('status', ['present', 'half_day'])->count(),
                default => 0,
            };
            $row = [
                'user_id' => $user->id,
                'name' => $user->name,
                'role_name' => $role->name,
                'aadhaar_no' => $this->aadhaarNo($user, $role->name),
                'user_details' => $this->userDetails($user, $role->name),
                'total_leave_taken' => $leaveTaken,
                'unpaid_leave_days' => $unpaidLeaveDays,
                'total_shifts_completed' => $totalShifts,
                'total_working_days' => $workingDays,
                'basic_salary' => $grossSalary,
                'deduction' => $componentDeduction + $leaveDeduction,
                'incentive' => $incentive,
                'salary_split' => collect($split)->values()->all(),
            ];

            return $this->applyUnauthorizedLeave($row, 0);
        });
    }

    private function usersForRoleAndDepot(string $roleName, int $depotId)
    {
        return User::role($roleName)
            ->where('is_active', true)
            ->with(['driverProfile.depot', 'housekeepingProfile.depot', 'staffProfile.designation', 'controllerProfile.depot', 'supervisorProfile.depot'])
            ->where(function ($query) use ($roleName, $depotId) {
                match ($roleName) {
                    'Driver' => $query->whereHas('driverProfile', fn($profile) => $profile->where('depot_id', $depotId)),
                    'Housekeeping' => $query->whereHas('housekeepingProfile', fn($profile) => $profile->where('depot_id', $depotId)),
                    'Controller' => $query->whereHas('controllerProfile', fn($profile) => $profile->where('depot_id', $depotId)),
                    'Supervisor' => $query->whereHas('supervisorProfile', fn($profile) => $profile->where('depot_id', $depotId)),
                    default => $query->whereHas('staffProfile', fn($profile) => $profile->where('depot_id', $depotId)),
                };
            })
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'email', 'phone', 'country_code', 'avatar', 'is_active']);
    }

    private function salarySplit(User $user, string $roleName): array
    {
        $designationId = $roleName === 'Staff' ? $user->staffProfile?->designation_id : null;
        $components = SalaryComponents::forRole($roleName, $designationId);
        $values = SalaryComponents::valuesFor($user);

        return $components
            ->map(fn($component) => [
                'id' => $component->id,
                'name' => $component->component_name,
                'type' => $component->type,
                'amount' => (float) ($values[$component->id] ?? $component->template_default_amount ?? 0),
                'selected' => true,
            ])
            ->values()
            ->all();
    }

    private function grossSalary(User $user, array $earningSplit): float
    {
        if ($earningSplit !== []) {
            return (float) collect($earningSplit)->sum('amount');
        }

        return (float) (
            $user->driverProfile?->salary
            ?? $user->housekeepingProfile?->salary
            ?? $user->staffProfile?->gross_salary
            ?? $user->controllerProfile?->gross_salary
            ?? $user->supervisorProfile?->gross_salary
            ?? 0
        );
    }

    private function aadhaarNo(User $user, string $roleName): ?string
    {
        return match ($roleName) {
            'Driver' => $user->driverProfile?->aadhaar_number,
            'Housekeeping' => $user->housekeepingProfile?->aadhaar_number,
            'Controller' => $user->controllerProfile?->aadhaar_number,
            'Supervisor' => $user->supervisorProfile?->aadhaar_number,
            default => $user->staffProfile?->aadhaar_number,
        };
    }

    private function userDetails(User $user, string $roleName): array
    {
        $profile = match ($roleName) {
            'Driver' => $user->driverProfile,
            'Housekeeping' => $user->housekeepingProfile,
            'Controller' => $user->controllerProfile,
            'Supervisor' => $user->supervisorProfile,
            default => $user->staffProfile,
        };

        return [
            'name' => $user->name,
            'avatar_url' => $user->avatar_url,
            'code' => $user->code ?: '-',
            'role' => $roleName,
            'phone' => $user->full_phone ?: '-',
            'email' => $user->email ?: '-',
            'aadhaar_number' => $this->aadhaarNo($user, $roleName) ?: '-',
            'depot' => $profile?->depot?->name ?? '-',
            'designation' => $user->staffProfile?->designation?->name ?? '-',
            'employment_type' => $profile?->employment_type_label ?: '-',
            'joining_date' => ($profile?->joining_date ?? $profile?->date_of_joining)?->format('d-m-Y') ?? '-',
        ];
    }

    private function applySelectedComponents(array $row, array $selectedIds): array
    {
        $selectedIds = collect($selectedIds)->map(fn($id) => (int) $id)->all();
        $split = collect($row['salary_split'])->map(function (array $component) use ($selectedIds) {
            $component['selected'] = in_array((int) $component['id'], $selectedIds, true);

            return $component;
        });
        $selected = $split->where('selected', true);

        if ($split->isNotEmpty()) {
            $earnings = $selected->where('type', 'earning');
            $row['incentive'] = (float) $earnings->filter(fn($item) => $this->isIncentiveComponent($item))->sum('amount');
            $row['basic_salary'] = (float) $earnings
                ->reject(fn($item) => $this->isIncentiveComponent($item))
                ->sum('amount');
            $row['deduction'] = (float) $selected->where('type', 'deduction')->sum('amount');
        }

        $row['salary_split'] = $split->values()->all();

        return $row;
    }

    private function leaveTaken(int $userId, Carbon $start, Carbon $end): float
    {
        return (float) Leave::where('user_id', $userId)
            ->where('status', 'Approved')
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('leave_date', [$start, $end])
                    ->orWhere(function ($subQuery) use ($start, $end) {
                        $subQuery->whereDate('from_date', '<=', $end)->whereDate('to_date', '>=', $start);
                    });
            })
            ->sum('number_of_days');
    }

    private function unpaidLeaveTaken(int $userId, Carbon $start, Carbon $end): float
    {
        return (float) Leave::where('user_id', $userId)
            ->whereIn('status', ['Pending', 'Approved', 'Auto Marked'])
            ->whereHas('leaveType', fn ($query) => $query->where('leave_category', 'Unpaid Leave'))
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('leave_date', [$start, $end])
                    ->orWhere(function ($subQuery) use ($start, $end) {
                        $subQuery->whereDate('from_date', '<=', $end)
                            ->whereDate('to_date', '>=', $start);
                    });
            })
            ->sum('number_of_days');
    }

    private function completedDriverShifts(User $user, Carbon $start, Carbon $end): int
    {
        $driverProfileId = $user->driverProfile?->id;

        if (! $driverProfileId) {
            return 0;
        }

        return TripSheetEntryDor::where('is_completed', true)
            ->whereBetween('dor_date', [$start, $end])
            ->whereHas('tripSheetEntry', fn($query) => $query->where('driver_profile_id', $driverProfileId))
            ->count();
    }

    private function applyUnauthorizedLeave(array $row, float $unauthorizedLeaves): array
    {
        $perDay = $row['total_working_days'] > 0 ? ((float) $row['basic_salary'] / $row['total_working_days']) : 0;
        $lop = round($perDay * $unauthorizedLeaves, 2);
        $row['unauthorized_leaves'] = $unauthorizedLeaves;
        $row['lop'] = $lop;
        $row['net_salary'] = round((float) $row['basic_salary'] + (float) $row['incentive'] - (float) $row['deduction'] - $lop, 2);

        return $row;
    }

    private function isIncentiveComponent(array $component): bool
    {
        return str($component['name'] ?? '')->lower()->contains('incent');
    }

    private function approvalStatusBadge(SalaryProcessing $salaryProcessing): string
    {
        $isApproved = $salaryProcessing->status === 'Approved';
        $label = $isApproved ? 'Approved' : 'Pending';
        $class = $isApproved ? 'status-green' : 'status-orange';

        return '<span class="' . $class . '">' . e($label) . '</span>';
    }

    private function storedRow(SalaryProcessingItem $item): array
    {
        $processing = $item->salaryProcessing;
        $start = $processing
            ? Carbon::create((int) $processing->year, (int) $processing->month, 1)->startOfMonth()
            : null;
        $unpaidLeaveDays = $start
            ? $this->unpaidLeaveTaken($item->user_id, $start, $start->copy()->endOfMonth())
            : 0;

        return [
            'user_id' => $item->user_id,
            'name' => $item->user?->name ?? '-',
            'role_name' => $item->salaryProcessing?->role?->name ?? '',
            'aadhaar_no' => $item->aadhaar_no,
            'user_details' => $this->userDetails($item->user, $item->salaryProcessing?->role?->name ?? ''),
            'total_leave_taken' => (float) $item->total_leave_taken,
            'unpaid_leave_days' => $unpaidLeaveDays,
            'total_shifts_completed' => $item->total_shifts_completed,
            'total_working_days' => $item->total_working_days,
            'lop' => (float) $item->lop,
            'basic_salary' => (float) $item->basic_salary,
            'deduction' => (float) $item->deduction,
            'incentive' => (float) $item->incentive,
            'unauthorized_leaves' => (float) $item->unauthorized_leaves,
            'net_salary' => (float) $item->net_salary,
            'salary_split' => collect($item->salary_split ?: [])->map(function ($component) {
                $component['selected'] = $component['selected'] ?? true;

                return $component;
            })->values()->all(),
        ];
    }

    private function commonData(): array
    {
        return [
            'months' => collect(range(1, 12))->mapWithKeys(fn($month) => [$month => Carbon::create((int) date('Y'), $month, 1)->format('F')])->all(),
            'years' => range((int) date('Y') - 5, (int) date('Y') + 1),
            'depots' => Depot::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'roles' => Role::whereIn('name', array_keys(Attendance::ROLES))->orderBy('name')->get(['id', 'name']),
            'paymentMethods' => SalaryProcessing::PAYMENT_METHODS,
        ];
    }
}
