<?php

namespace App\Http\Controllers;

use App\Exports\LeaveExport;
use App\Models\GeneralSetting;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class LeaveController extends Controller implements HasMiddleware
{
    private const GENERAL_LEAVE_ROLES = ['Supervisor', 'Controller', 'Staff', 'Housekeeping'];

    private const FILTER_ROLES = ['Supervisor', 'Controller', 'Staff', 'Driver', 'Housekeeping'];

    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('leaves.view'), ['index', 'show', 'export', 'consolidatedReportData', 'downloadConsolidatedReport']),
            new Middleware(PermissionMiddleware::using('leaves.create'), ['createGeneral', 'createDriver', 'store']),
            new Middleware(PermissionMiddleware::using('leaves.edit'), ['edit', 'update', 'changeStatus']),
            new Middleware(PermissionMiddleware::using('leaves.delete'), ['destroy']),
            new Middleware(PermissionMiddleware::using('leaves.view|leaves.create|leaves.edit'), ['balances']),
        ];
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $type = $request->input('table_type', 'all');
            $query = $this->filteredQuery($type);

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', fn (Leave $row) => '<input type="checkbox" class="row-check" value="' . $row->id . '">')
                ->addColumn('code_display', fn (Leave $row) => $row->code ?: '-')
                ->addColumn('employee_name', fn (Leave $row) => $row->user?->name ?? '-')
                ->addColumn('role_name', fn (Leave $row) => $row->user?->roles?->pluck('name')->implode(', ') ?: '-')
                ->addColumn('leave_type_name', fn (Leave $row) => $row->leaveType?->short_name ?: $row->leaveType?->leave_name ?: $row->driver_leave_type ?: '-')
                ->addColumn('from_display', fn (Leave $row) => $row->from_date?->format('d M') ?? '-')
                ->addColumn('to_display', fn (Leave $row) => $row->to_date?->format('d M') ?? '-')
                ->addColumn('date_display', fn (Leave $row) => $row->leave_date?->format('d M') ?? '-')
                ->addColumn('days_display', fn (Leave $row) => $row->number_of_days !== null ? rtrim(rtrim((string) $row->number_of_days, '0'), '.') : '-')
                ->addColumn('status_badge', fn (Leave $row) => $this->statusBadge($row->status))
                ->addColumn('action', fn (Leave $row) => view('leave-management.partials.action', compact('row'))->render())
                ->rawColumns(['checkbox', 'status_badge', 'action'])
                ->make(true);
        }

        return view('leave-management.index', [
            'statuses' => Leave::STATUSES,
            'leaveTypes' => $this->activeLeaveTypesFor('general')
                ->get(['id', 'leave_name', 'short_name', 'max_leaves_per_year', 'allow_half_day']),
            'driverLeaveTypes' => $this->activeLeaveTypesFor('driver')
                ->get(['id', 'leave_name', 'short_name', 'max_leaves_per_year', 'allow_half_day']),
            'filterRoles' => self::FILTER_ROLES,
            'filterUsersByRole' => collect(self::FILTER_ROLES)
                ->mapWithKeys(fn (string $role) => [
                    $role => User::role($role)
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->get(['id', 'code', 'name'])
                        ->map(fn (User $user) => [
                            'name' => trim(($user->code ? $user->code . ' - ' : '') . $user->name),
                            'search_name' => $user->name,
                        ])
                        ->values(),
                ]),
        ]);
    }

    public function createGeneral()
    {
        return view('leave-management.general-form', $this->formData() + [
            'record' => null,
        ]);
    }

    public function createDriver()
    {
        return view('leave-management.driver-form', $this->formData() + [
            'record' => null,
        ]);
    }

    public function balances(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'leave_for' => ['required', Rule::in(array_keys(Leave::TYPES))],
            'leave_type_id' => ['nullable', 'integer', 'exists:leave_types,id'],
            'exclude_leave_id' => ['nullable', 'integer', 'exists:leaves,id'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'day_type' => ['nullable', Rule::in(array_keys(Leave::DAY_TYPES))],
        ]);

        [$financialYearStart, $financialYearEnd] = $this->financialYearRange();

        if (in_array($validated['leave_for'], ['general', 'driver'], true)) {
            $leaveTypes = $this->activeLeaveTypesFor($validated['leave_for'])
                ->get(['id', 'leave_name', 'short_name', 'max_leaves_per_year']);

            $requestedDays = $this->requestedBalanceDays($validated);
            $selectedLeaveTypeId = (int) ($validated['leave_type_id'] ?? 0);
            $balances = $leaveTypes->map(fn (LeaveType $leaveType) => $this->leaveTypeBalance(
                (int) $validated['user_id'],
                $leaveType,
                $financialYearStart,
                $financialYearEnd,
                $validated['exclude_leave_id'] ?? null,
                $validated['leave_for'],
                $leaveType->id === $selectedLeaveTypeId ? $requestedDays : 0
            ))->values();

            return response()->json([
                'financial_year' => [
                    'from' => $financialYearStart->toDateString(),
                    'to' => $financialYearEnd->toDateString(),
                ],
                'balances' => $balances,
                'selected' => isset($validated['leave_type_id'])
                    ? $balances->firstWhere('leave_type_id', (int) $validated['leave_type_id'])
                    : null,
            ]);
        }
    }

    public function store(Request $request)
    {
        $validated = $this->validatedData($request);
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        if ($request->hasFile('attachment')) {
            $validated['attachment_path'] = $request->file('attachment')->store('leave-attachments', 'public');
        }

        $leave = Leave::create($validated);
        $leave->code = $this->generateLeaveCode($leave->id);
        $leave->save();

        return redirect()->route('leaves.index')->with('success', 'Leave created successfully.');
    }

    public function edit(Leave $leave)
    {
        return view($leave->leave_for === 'driver' ? 'leave-management.driver-form' : 'leave-management.general-form', $this->formData() + [
            'record' => $leave,
        ]);
    }

    public function show(Leave $leave)
    {
        $leave->load(['user.roles', 'leaveType', 'creator', 'updater']);
        [$financialYearStart, $financialYearEnd] = $this->financialYearRange();
        $balances = $this->activeLeaveTypesFor($leave->leave_for)
            ->get()
            ->map(fn (LeaveType $leaveType) => $this->leaveTypeBalance(
                $leave->user_id,
                $leaveType,
                $financialYearStart,
                $financialYearEnd,
                null,
                $leave->leave_for
            ));

        return view('leave-management.show', [
            'record' => $leave,
            'financialYearStart' => $financialYearStart,
            'financialYearEnd' => $financialYearEnd,
            'balances' => $balances,
            'selectedBalance' => $balances->firstWhere('leave_type_id', $leave->leave_type_id),
        ]);
    }

    public function update(Request $request, Leave $leave)
    {
        $validated = $this->validatedData($request, $leave);
        $validated['updated_by'] = auth()->id();

        if ($request->hasFile('attachment')) {
            if ($leave->attachment_path) {
                Storage::disk('public')->delete($leave->attachment_path);
            }

            $validated['attachment_path'] = $request->file('attachment')->store('leave-attachments', 'public');
        }

        $leave->update($validated);

        if (! $leave->code) {
            $leave->code = $this->generateLeaveCode($leave->id);
            $leave->save();
        }

        return redirect()->route('leaves.index')->with('success', 'Leave updated successfully.');
    }

    public function destroy(Leave $leave)
    {
        if ($leave->attachment_path) {
            Storage::disk('public')->delete($leave->attachment_path);
        }

        $leave->delete();

        return response()->json([
            'success' => true,
            'message' => 'Leave deleted successfully.',
        ]);
    }

    public function changeStatus(Request $request, Leave $leave)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(Leave::STATUSES))],
            'remarks' => ['nullable', 'string'],
        ]);

        $leave->update($validated + [
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Leave status updated successfully.',
        ]);
    }

    public function export(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:leaves,id'],
        ]);

        $query = $this->filteredQuery($request->input('table_type', 'all'));
        $query->whereIn('leaves.id', $validated['ids']);

        return Excel::download(new LeaveExport($query), 'leave-management.xlsx');
    }

    public function consolidatedReportData(Request $request)
    {
        $query = $this->filteredQuery('all');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('code_display', fn (Leave $row) => $row->code ?: '-')
            ->addColumn('employee_name', fn (Leave $row) => $row->user?->name ?? '-')
            ->addColumn('role_name', fn (Leave $row) => $row->user?->roles?->pluck('name')->implode(', ') ?: '-')
            ->addColumn('leave_type_name', fn (Leave $row) => $row->leaveType?->short_name ?: $row->leaveType?->leave_name ?: $row->driver_leave_type ?: '-')
            ->addColumn('from_display', fn (Leave $row) => $row->from_date?->format('d M Y') ?? $row->leave_date?->format('d M Y') ?? '-')
            ->addColumn('to_display', fn (Leave $row) => $row->to_date?->format('d M Y') ?? $row->leave_date?->format('d M Y') ?? '-')
            ->addColumn('days_display', fn (Leave $row) => $row->number_of_days !== null ? $this->formatDays((float) $row->number_of_days) : '-')
            ->addColumn('status_badge', fn (Leave $row) => $this->statusBadge($row->status))
            ->rawColumns(['status_badge'])
            ->make(true);
    }

    public function downloadConsolidatedReport(Request $request)
    {
        $records = $this->filteredQuery('all')->get();
        $pdf = $this->buildConsolidatedReportPdf($records);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="consolidated-leave-report.pdf"',
        ]);
    }

    private function filteredQuery(string $type)
    {
        $query = Leave::with(['user.roles', 'leaveType'])->select('leaves.*')->latest('leaves.created_at');

        if (in_array($type, ['general', 'driver'], true)) {
            $query->where('leave_for', $type);
        }

        if (request()->filled('status')) {
            $query->where('status', request('status'));
        }

        if (request()->filled('employee_name')) {
            $employeeName = request('employee_name');
            $query->whereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%' . $employeeName . '%'));
        }

        if (request()->filled('role') && in_array(request('role'), self::FILTER_ROLES, true)) {
            $query->whereHas('user.roles', fn ($roleQuery) => $roleQuery->where('name', request('role')));
        }

        if (request()->filled('leave_type')) {
            [$leaveTypeFor, $leaveTypeValue] = array_pad(explode(':', request('leave_type'), 2), 2, null);

            if ($leaveTypeFor === 'general' && $leaveTypeValue) {
                $query->where('leave_for', 'general')->where('leave_type_id', $leaveTypeValue);
            }

            if ($leaveTypeFor === 'driver' && $leaveTypeValue) {
                $query->where('leave_for', 'driver')->where('leave_type_id', $leaveTypeValue);
            }
        }

        if (request()->filled('from_date') || request()->filled('to_date')) {
            $fromDate = request('from_date');
            $toDate = request('to_date');

            $query->where(function ($subQuery) use ($fromDate, $toDate) {
                if ($fromDate && $toDate) {
                    $subQuery->where(function ($rangeQuery) use ($fromDate, $toDate) {
                        $rangeQuery->whereDate('from_date', '<=', $toDate)
                            ->whereDate('to_date', '>=', $fromDate);
                    })->orWhereBetween('leave_date', [$fromDate, $toDate]);

                    return;
                }

                if ($fromDate) {
                    $subQuery->whereDate('to_date', '>=', $fromDate)
                        ->orWhereDate('leave_date', '>=', $fromDate);

                    return;
                }

                $subQuery->whereDate('from_date', '<=', $toDate)
                    ->orWhereDate('leave_date', '<=', $toDate);
            });
        }

        return $query;
    }

    private function validatedData(Request $request, ?Leave $leave = null): array
    {
        $leaveFor = $request->input('leave_for', $leave?->leave_for);
        $rules = [
            'leave_for' => ['required', Rule::in(array_keys(Leave::TYPES))],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'reason' => ['nullable', 'string'],
            'status' => ['required', Rule::in(array_keys(Leave::STATUSES))],
            'attachment' => ['nullable', 'file', 'max:5120'],
        ];

        if ($leaveFor === 'general') {
            $rules += [
                'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
                'from_date' => ['required', 'date'],
                'to_date' => ['required', 'date', 'after_or_equal:from_date'],
                'day_type' => ['required', Rule::in(array_keys(Leave::DAY_TYPES))],
                'number_of_days' => ['nullable', 'numeric', 'min:0.5'],
            ];
        } else {
            $rules += [
                'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
                'from_date' => ['required', 'date'],
                'to_date' => ['required', 'date', 'after_or_equal:from_date'],
                'day_type' => ['required', Rule::in(array_keys(Leave::DAY_TYPES))],
                'number_of_days' => ['nullable', 'numeric', 'min:0.5'],
                'shift' => ['required', Rule::in(array_keys(Leave::SHIFTS))],
                'assigned_vehicle_route' => ['required', 'string', 'max:255'],
                'reason' => ['required', 'string'],
            ];
        }

        $validated = $request->validate($rules);

        if ($leaveFor === 'driver' && ! User::role(['Driver', 'Housekeeping'])->whereKey($validated['user_id'])->exists()) {
            throw ValidationException::withMessages(['user_id' => 'Please select a valid driver or housekeeping employee.']);
        }

        if ($leaveFor === 'general' && ! User::role(self::GENERAL_LEAVE_ROLES)->whereKey($validated['user_id'])->exists()) {
            throw ValidationException::withMessages(['user_id' => 'Please select a valid supervisor, controller, or staff user.']);
        }

        $this->validateLeaveTypeApplies($validated, $leaveFor);
        $validated = $this->applyCalculatedDays($validated, $leaveFor);
        $this->validateWithinFinancialYear($validated, $leaveFor);
        $this->validateLeaveLimit($validated, $leaveFor, $leave);

        return collect($validated)->except('attachment')->all();
    }

    private function formData(): array
    {
        return [
            'generatedCode' => generate_code('Leave Management Module', ((int) Leave::max('id')) + 1, 3, 'LVM'),
            'employees' => User::role(self::GENERAL_LEAVE_ROLES)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
            'employeesByRole' => collect(self::GENERAL_LEAVE_ROLES)
                ->mapWithKeys(fn (string $role) => [
                    $role => User::role($role)
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->get(['id', 'code', 'name'])
                        ->map(fn (User $user) => [
                            'id' => $user->id,
                            'name' => trim(($user->code ? $user->code . ' - ' : '') . $user->name),
                        ])
                        ->values(),
                ]),
            'generalLeaveRoles' => self::GENERAL_LEAVE_ROLES,
            'drivers' => User::role(['Driver', 'Housekeeping'])->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'leaveTypes' => $this->activeLeaveTypesFor('general')->get(['id', 'leave_name', 'short_name', 'max_leaves_per_year', 'allow_half_day']),
            'driverLeaveTypes' => $this->activeLeaveTypesFor('driver')->get(['id', 'leave_name', 'short_name', 'max_leaves_per_year', 'allow_half_day']),
            'shifts' => Leave::SHIFTS,
            'dayTypes' => Leave::DAY_TYPES,
            'statuses' => Leave::STATUSES,
        ];
    }

    private function statusBadge(string $status): string
    {
        return match ($status) {
            'Approved' => '<span class="status-green">Approved</span>',
            'Rejected', 'Cancelled', 'Auto Marked' => '<span class="status-red">' . e($status) . '</span>',
            default => '<span class="status-orange">Pending</span>',
        };
    }

    private function generateLeaveCode(int $id): string
    {
        return generate_code('Leave Management Module', $id, 3, 'LVM');
    }

    private function applyCalculatedDays(array $validated, string $leaveFor): array
    {
        if ($leaveFor === 'driver') {
            $from = Carbon::parse($validated['from_date'])->startOfDay();
            $to = Carbon::parse($validated['to_date'])->startOfDay();

            if ($validated['day_type'] === 'half_day') {
                if (! $from->isSameDay($to)) {
                    throw ValidationException::withMessages([
                        'day_type' => 'Half day leave can be applied only for a single date.',
                    ]);
                }

                $leaveType = LeaveType::find($validated['leave_type_id']);
                if ($leaveType && ! $leaveType->allow_half_day) {
                    throw ValidationException::withMessages([
                        'day_type' => 'Selected leave type does not allow half day leave.',
                    ]);
                }

                $validated['number_of_days'] = 0.5;
            } else {
                $validated['number_of_days'] = $from->diffInDays($to) + 1;
            }

            $validated['leave_date'] = $validated['from_date'];
            $validated['driver_leave_type'] = null;

            return $validated;
        }

        $from = Carbon::parse($validated['from_date'])->startOfDay();
        $to = Carbon::parse($validated['to_date'])->startOfDay();

        if ($validated['day_type'] === 'half_day') {
            if (! $from->isSameDay($to)) {
                throw ValidationException::withMessages([
                    'day_type' => 'Half day leave can be applied only for a single date.',
                ]);
            }

            $leaveType = LeaveType::find($validated['leave_type_id']);
            if ($leaveType && ! $leaveType->allow_half_day) {
                throw ValidationException::withMessages([
                    'day_type' => 'Selected leave type does not allow half day leave.',
                ]);
            }

            $validated['number_of_days'] = 0.5;

            return $validated;
        }

        $validated['number_of_days'] = $from->diffInDays($to) + 1;

        return $validated;
    }

    private function validateLeaveLimit(array $validated, string $leaveFor, ?Leave $leave = null): void
    {
        $leaveType = LeaveType::find($validated['leave_type_id']);
        if (! $leaveType || $leaveType->max_leaves_per_year === null) {
            return;
        }

        [$financialYearStart, $financialYearEnd] = $this->financialYearRange();
        $balance = $this->leaveTypeBalance(
            (int) $validated['user_id'],
            $leaveType,
            $financialYearStart,
            $financialYearEnd,
            $leave?->id,
            $leaveFor
        );

        if ((float) $validated['number_of_days'] > (float) $balance['remaining']) {
            throw ValidationException::withMessages([
                'number_of_days' => 'This employee has only ' . $this->formatDays($balance['remaining']) . ' day(s) remaining for this leave type in the selected financial year.',
            ]);
        }
    }

    private function validateWithinFinancialYear(array $validated, string $leaveFor): void
    {
        [$financialYearStart, $financialYearEnd] = $this->financialYearRange();
        $from = Carbon::parse($validated['from_date'])->startOfDay();
        $to = Carbon::parse($validated['to_date'])->startOfDay();

        if ($from->lt($financialYearStart) || $to->gt($financialYearEnd)) {
            throw ValidationException::withMessages([
                'from_date' => 'Leave dates must be within the configured financial year.',
            ]);
        }
    }

    private function validateLeaveTypeApplies(array $validated, string $leaveFor): void
    {
        $leaveType = $this->activeLeaveTypesFor($leaveFor)
            ->whereKey($validated['leave_type_id'] ?? null)
            ->first();

        if (! $leaveType) {
            throw ValidationException::withMessages([
                'leave_type_id' => 'Please select a valid leave type for this leave form.',
            ]);
        }
    }

    private function leaveTypeBalance(
        int $userId,
        LeaveType $leaveType,
        Carbon $financialYearStart,
        Carbon $financialYearEnd,
        ?int $excludeLeaveId = null,
        string $leaveFor = 'general',
        float $requestedDays = 0
    ): array {
        $used = Leave::query()
            ->where('user_id', $userId)
            ->where('leave_for', $leaveFor)
            ->where('leave_type_id', $leaveType->id)
            ->whereRaw("LOWER(status) IN ('pending', 'approved', 'auto marked')")
            ->when($excludeLeaveId, fn ($query) => $query->whereKeyNot($excludeLeaveId))
            ->where(function ($query) use ($financialYearStart, $financialYearEnd) {
                $query->whereBetween('from_date', [$financialYearStart->toDateString(), $financialYearEnd->toDateString()])
                    ->orWhereBetween('to_date', [$financialYearStart->toDateString(), $financialYearEnd->toDateString()])
                    ->orWhere(function ($rangeQuery) use ($financialYearStart, $financialYearEnd) {
                        $rangeQuery->whereDate('from_date', '<=', $financialYearStart->toDateString())
                            ->whereDate('to_date', '>=', $financialYearEnd->toDateString());
                    })
                    ->orWhereBetween('leave_date', [$financialYearStart->toDateString(), $financialYearEnd->toDateString()]);
            })
            ->sum('number_of_days');

        $limit = $leaveType->max_leaves_per_year !== null ? (float) $leaveType->max_leaves_per_year : null;

        return [
            'leave_type_id' => $leaveType->id,
            'label' => $leaveType->short_name ?: $leaveType->leave_name,
            'limit' => $limit,
            'used' => (float) $used,
            'requested' => $requestedDays,
            'remaining' => $limit === null ? null : max(0, $limit - (float) $used),
            'remaining_after_request' => $limit === null ? null : max(0, $limit - (float) $used - $requestedDays),
        ];
    }

    private function requestedBalanceDays(array $validated): float
    {
        if (empty($validated['from_date']) || empty($validated['to_date'])) {
            return 0;
        }

        if (($validated['day_type'] ?? 'full_day') === 'half_day') {
            return 0.5;
        }

        return (float) (Carbon::parse($validated['from_date'])->startOfDay()
            ->diffInDays(Carbon::parse($validated['to_date'])->startOfDay()) + 1);
    }

    private function usedDriverLeaves(
        int $userId,
        Carbon $financialYearStart,
        Carbon $financialYearEnd,
        ?int $excludeLeaveId = null
    ): float {
        return (float) Leave::query()
            ->where('user_id', $userId)
            ->where('leave_for', 'driver')
            ->whereRaw("LOWER(status) IN ('pending', 'approved', 'auto marked')")
            ->when($excludeLeaveId, fn ($query) => $query->whereKeyNot($excludeLeaveId))
            ->whereBetween('leave_date', [$financialYearStart->toDateString(), $financialYearEnd->toDateString()])
            ->sum('number_of_days');
    }

    private function activeLeaveTypesFor(string $leaveFor)
    {
        $applicable = $leaveFor === 'driver'
            ? ['all_employees', 'drivers', 'housekeeping']
            : ['all_employees', 'controllers', 'supervisors', 'staff', 'housekeeping'];

        return LeaveType::query()
            ->where('is_active', true)
            ->whereIn('applicable_for', $applicable)
            ->orderBy('leave_name');
    }

    private function financialYearRange(): array
    {
        $setting = GeneralSetting::query()->first();
        $fromYear = (int) ($setting?->financial_year ?: now()->year);
        $fromMonth = (int) ($setting?->financial_year_from_month ?: 4);
        $toYear = (int) ($setting?->financial_year_to_year ?: $fromYear + 1);
        $toMonth = (int) ($setting?->financial_year_to_month ?: 3);

        return [
            Carbon::create($fromYear, $fromMonth, 1)->startOfDay(),
            Carbon::create($toYear, $toMonth, 1)->endOfMonth()->startOfDay(),
        ];
    }

    private function formatDays(float|int|null $days): string
    {
        if ($days === null) {
            return '-';
        }

        return rtrim(rtrim(number_format((float) $days, 2, '.', ''), '0'), '.');
    }

    private function buildConsolidatedReportPdf($records): string
    {
        $content = "%PDF-1.4\n";
        $objects = [];
        $lines = [
            'Consolidated Leave Report',
            'Generated on ' . now()->format('d-m-Y H:i'),
            '',
            'Code | Employee | User Type | Leave Type | From | To | Days | Status',
            str_repeat('-', 118),
        ];

        foreach ($records as $record) {
            $lines[] = implode(' | ', [
                $record->code ?: '-',
                $record->user?->name ?: '-',
                $record->user?->roles?->pluck('name')->implode(', ') ?: '-',
                $record->leaveType?->short_name ?: $record->leaveType?->leave_name ?: $record->driver_leave_type ?: '-',
                $record->from_date?->format('d-m-Y') ?? $record->leave_date?->format('d-m-Y') ?? '-',
                $record->to_date?->format('d-m-Y') ?? $record->leave_date?->format('d-m-Y') ?? '-',
                $record->number_of_days !== null ? $this->formatDays((float) $record->number_of_days) : '-',
                $record->status ?: '-',
            ]);
        }

        if ($records->isEmpty()) {
            $lines[] = 'No records found.';
        }

        $stream = "BT\n/F1 8 Tf\n32 555 Td\n";
        foreach (array_slice($lines, 0, 40) as $index => $line) {
            $stream .= ($index ? "0 -13 Td\n" : '') . '(' . $this->pdfEscape(Str::limit($line, 150, '...')) . ") Tj\n";
        }
        $stream .= "ET";

        $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objects[] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
        $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>";
        $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
        $objects[] = "<< /Length " . strlen($stream) . " >>\nstream\n{$stream}\nendstream";

        $offsets = [0];
        foreach ($objects as $number => $object) {
            $offsets[] = strlen($content);
            $content .= ($number + 1) . " 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($content);
        $content .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $content .= sprintf("%010d 00000 n \n", $offset);
        }

        return $content . "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }

    private function pdfEscape(string $value): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
    }
}
