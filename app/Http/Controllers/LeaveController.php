<?php

namespace App\Http\Controllers;

use App\Exports\LeaveExport;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class LeaveController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('leaves.view'), ['index', 'export']),
            new Middleware(PermissionMiddleware::using('leaves.create'), ['createGeneral', 'createDriver', 'store']),
            new Middleware(PermissionMiddleware::using('leaves.edit'), ['edit', 'update', 'changeStatus']),
            new Middleware(PermissionMiddleware::using('leaves.delete'), ['destroy']),
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
                ->addColumn('employee_name', fn (Leave $row) => $row->user?->name ?? '-')
                ->addColumn('role_name', fn (Leave $row) => $row->user?->roles?->pluck('name')->implode(', ') ?: '-')
                ->addColumn('leave_type_name', fn (Leave $row) => $row->leave_for === 'driver'
                    ? ($row->driver_leave_type ?: '-')
                    : ($row->leaveType?->short_name ?: $row->leaveType?->leave_name ?: '-'))
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
            'leaveTypes' => LeaveType::where('is_active', true)->orderBy('leave_name')->get(['id', 'leave_name', 'short_name']),
            'driverLeaveTypes' => Leave::DRIVER_LEAVE_TYPES,
            'filterRoles' => ['Supervisor', 'Controller', 'Driver'],
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

    public function store(Request $request)
    {
        $validated = $this->validatedData($request);
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        if ($request->hasFile('attachment')) {
            $validated['attachment_path'] = $request->file('attachment')->store('leave-attachments', 'public');
        }

        Leave::create($validated);

        return redirect()->route('leaves.index')->with('success', 'Leave created successfully.');
    }

    public function edit(Leave $leave)
    {
        return view($leave->leave_for === 'driver' ? 'leave-management.driver-form' : 'leave-management.general-form', $this->formData() + [
            'record' => $leave,
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

        if (request()->filled('role') && in_array(request('role'), ['Supervisor', 'Controller', 'Driver'], true)) {
            $query->whereHas('user.roles', fn ($roleQuery) => $roleQuery->where('name', request('role')));
        }

        if (request()->filled('leave_type')) {
            [$leaveTypeFor, $leaveTypeValue] = array_pad(explode(':', request('leave_type'), 2), 2, null);

            if ($leaveTypeFor === 'general' && $leaveTypeValue) {
                $query->where('leave_for', 'general')->where('leave_type_id', $leaveTypeValue);
            }

            if ($leaveTypeFor === 'driver' && $leaveTypeValue) {
                $query->where('leave_for', 'driver')->where('driver_leave_type', $leaveTypeValue);
            }
        }

        if (request()->filled('leave_date')) {
            $leaveDate = request('leave_date');
            $query->where(function ($subQuery) use ($leaveDate) {
                $subQuery->whereDate('leave_date', $leaveDate)
                    ->orWhere(function ($dateRangeQuery) use ($leaveDate) {
                        $dateRangeQuery->whereDate('from_date', '<=', $leaveDate)
                            ->whereDate('to_date', '>=', $leaveDate);
                    });
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
                'number_of_days' => ['required', 'numeric', 'min:0.5'],
            ];
        } else {
            $rules += [
                'driver_leave_type' => ['required', Rule::in(array_keys(Leave::DRIVER_LEAVE_TYPES))],
                'leave_date' => ['required', 'date'],
                'shift' => ['required', Rule::in(array_keys(Leave::SHIFTS))],
                'assigned_vehicle_route' => ['required', 'string', 'max:255'],
                'reason' => ['required', 'string'],
            ];
        }

        $validated = $request->validate($rules);

        if ($leaveFor === 'driver' && ! User::role('Driver')->whereKey($validated['user_id'])->exists()) {
            throw ValidationException::withMessages(['user_id' => 'Please select a valid driver.']);
        }

        if ($leaveFor === 'general' && ! User::role(['Supervisor', 'Controller'])->whereKey($validated['user_id'])->exists()) {
            throw ValidationException::withMessages(['user_id' => 'Please select a valid supervisor or controller.']);
        }

        return collect($validated)->except('attachment')->all();
    }

    private function formData(): array
    {
        return [
            'employees' => User::role(['Supervisor', 'Controller'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
            'drivers' => User::role('Driver')->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'leaveTypes' => LeaveType::where('is_active', true)->orderBy('leave_name')->get(['id', 'leave_name', 'short_name']),
            'driverLeaveTypes' => Leave::DRIVER_LEAVE_TYPES,
            'shifts' => Leave::SHIFTS,
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
}
