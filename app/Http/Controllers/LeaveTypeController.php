<?php

namespace App\Http\Controllers;

use App\Exports\LeaveTypeExport;
use App\Http\Requests\StoreLeaveTypeRequest;
use App\Http\Requests\UpdateLeaveTypeRequest;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class LeaveTypeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('leave-types.view'), ['index', 'show', 'export']),
            new Middleware(PermissionMiddleware::using('leave-types.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('leave-types.edit'), ['edit', 'update', 'status']),
            new Middleware(PermissionMiddleware::using('leave-types.delete'), ['destroy']),
        ];
    }

    public function index()
    {
        if (request()->ajax()) {
            $query = LeaveType::select([
                'id',
                'code',
                'leave_name',
                'leave_category',
                'max_leaves_per_year',
                'applicable_for',
                'is_active',
                'created_at',
            ])->orderBy('created_at', 'desc');

            if (request()->filled('search_text')) {
                $search = request('search_text');
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('code', 'like', '%' . $search . '%')
                        ->orWhere('leave_name', 'like', '%' . $search . '%');
                });
            }

            if (request()->filled('leave_category')) {
                $query->where('leave_category', request('leave_category'));
            }

            if (request()->filled('applicable_for')) {
                $query->where('applicable_for', request('applicable_for'));
            }

            if (request()->filled('status') && in_array(request('status'), ['0', '1'], true)) {
                $query->where('is_active', request('status'));
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
                })
                ->editColumn('max_leaves_per_year', function ($row) {
                    return $row->max_leaves_per_year ?? 'Unlimited';
                })
                ->addColumn('status', function ($row) {
                    return $row->is_active
                        ? '<span class="status-green">Active</span>'
                        : '<span class="status-red">Inactive</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('leave-type.partials.action', compact('row'))->render();
                })
                ->rawColumns(['action', 'status', 'checkbox'])
                ->make(true);
        }

        $categories = LeaveType::CATEGORIES;
        $applicableFor = LeaveType::APPLICABLE_FOR;

        return view('leave-type.index', compact('categories', 'applicableFor'));
    }

    public function create()
    {
        $generatedCode = generate_code('Leave Type Module', ((int) LeaveType::max('id')) + 1, 3, 'LV');
        $categories = LeaveType::CATEGORIES;
        $applicableFor = LeaveType::APPLICABLE_FOR;
        $genders = LeaveType::GENDERS;

        return view('leave-type.form', compact('generatedCode', 'categories', 'applicableFor', 'genders'));
    }

    public function store(StoreLeaveTypeRequest $request)
    {
        $leaveType = LeaveType::create($request->validated());
        $leaveType->code = generate_code('Leave Type Module', $leaveType->id, 3, 'LV');
        $leaveType->save();

        return redirect()
            ->route('leave-types.index')
            ->with('success', 'Leave type created successfully.');
    }

    public function show(LeaveType $leaveType)
    {
        return view('leave-type.show', compact('leaveType'));
    }

    public function edit(LeaveType $leaveType)
    {
        $record = $leaveType;
        $categories = LeaveType::CATEGORIES;
        $applicableFor = LeaveType::APPLICABLE_FOR;
        $genders = LeaveType::GENDERS;

        return view('leave-type.form', compact('record', 'categories', 'applicableFor', 'genders'));
    }

    public function update(UpdateLeaveTypeRequest $request, LeaveType $leaveType)
    {
        $leaveType->update($request->validated());

        return redirect()
            ->route('leave-types.index')
            ->with('success', 'Leave type updated successfully.');
    }

    public function destroy(LeaveType $leaveType)
    {
        $leaveType->delete();

        return response()->json([
            'success' => true,
            'message' => 'Leave type deleted successfully.',
        ]);
    }

    public function export(Request $request)
    {
        $ids = $request->input('ids', []);
        $query = LeaveType::select(
            'code',
            'leave_name',
            'short_name',
            'leave_category',
            'max_leaves_per_year',
            'carry_forward_allowed',
            'max_carry_forward_limit',
            'encashment_allowed',
            'applicable_for',
            'gender_specific',
            'minimum_service_required',
            'minimum_leave_days',
            'maximum_leave_days_per_request',
            'advance_notice_days',
            'allow_half_day',
            'requires_approval',
            'is_active',
            'description',
            'remarks',
            'created_at'
        );

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        return Excel::download(new LeaveTypeExport($query), 'leave-types.xlsx');
    }

    public function status(Request $request)
    {
        $request->validate([
            'id' => ['required', 'integer', 'exists:leave_types,id'],
            'status' => ['required', 'boolean'],
        ]);

        $leaveType = LeaveType::findOrFail($request->id);
        $leaveType->is_active = $request->status;
        $leaveType->save();

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }
}
