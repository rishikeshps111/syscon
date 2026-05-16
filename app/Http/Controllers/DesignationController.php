<?php

namespace App\Http\Controllers;

use App\Exports\DesignationExport;
use App\Http\Requests\StoreDesignationRequest;
use App\Http\Requests\UpdateDesignationRequest;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Level;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class DesignationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('designations.view'), ['index', 'show', 'export']),
            new Middleware(PermissionMiddleware::using('designations.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('designations.edit'), ['edit', 'update', 'status']),
            new Middleware(PermissionMiddleware::using('designations.delete'), ['destroy']),
        ];
    }

    public function index()
    {
        if (request()->ajax()) {
            $query = Designation::with(['department', 'level', 'reportingRole'])
                ->select(['id', 'department_id', 'level_id', 'reporting_to', 'code', 'name', 'is_active', 'created_at'])
                ->orderBy('created_at', 'desc');

            if (request()->filled('department_id')) {
                $query->where('department_id', request('department_id'));
            }

            if (request()->filled('level_id')) {
                $query->where('level_id', request('level_id'));
            }

            if (request()->filled('status') && in_array(request('status'), ['0', '1'], true)) {
                $query->where('is_active', request('status'));
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
                })
                ->addColumn('department_name', function ($row) {
                    return $row->department?->name ?? '';
                })
                ->addColumn('level_name', function ($row) {
                    return $row->level?->name ?? '';
                })
                ->addColumn('reporting_to_name', function ($row) {
                    return $row->reportingRole?->name ?? '';
                })
                ->addColumn('status', function ($row) {
                    return $row->is_active
                        ? '<span class="status-green">Active</span>'
                        : '<span class="status-red">Inactive</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('designation.partials.action', compact('row'))->render();
                })
                ->rawColumns(['action', 'status', 'checkbox'])
                ->make(true);
        }

        $departments = Department::orderBy('name')->get(['id', 'name']);
        $levels = Level::orderBy('name')->get(['id', 'name']);

        return view('designation.index', compact('departments', 'levels'));
    }

    public function create(Request $request)
    {
        $departments = Department::orderBy('name')->get(['id', 'name']);
        $levels = Level::orderBy('name')->get(['id', 'name']);
        $roles = Role::where('name', '!=', 'Super Admin')->orderBy('name')->get(['id', 'name']);

        if ($request->id) {
            $record = Designation::findOrFail($request->id);

            return response()->json([
                'html' => view('designation.form', compact('record', 'departments', 'levels', 'roles'))->render(),
                'title' => 'Update Designation',
            ]);
        }

        $generatedCode = generate_code('Designation Module', ((int) Designation::max('id')) + 1, 3, 'DSG');

        return response()->json([
            'html' => view('designation.form', compact('generatedCode', 'departments', 'levels', 'roles'))->render(),
            'title' => 'Add Designation',
        ]);
    }

    public function store(StoreDesignationRequest $request)
    {
        $designation = Designation::create($request->validated());
        $designation->code = generate_code('Designation Module', $designation->id, 3, 'DSG');
        $designation->save();

        return response()->json([
            'success' => true,
            'message' => 'Designation created successfully.',
            'data' => $designation,
        ], 201);
    }

    public function show(Designation $designation) {}

    public function edit(Designation $designation) {}

    public function update(UpdateDesignationRequest $request, Designation $designation)
    {
        $designation->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Designation updated successfully.',
            'data' => $designation->fresh(),
        ]);
    }

    public function destroy(Designation $designation)
    {
        $designation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Designation deleted successfully.',
        ]);
    }

    public function export(Request $request)
    {
        $ids = $request->input('ids', []);
        $query = Designation::with(['department', 'level', 'reportingRole'])
            ->select('department_id', 'level_id', 'reporting_to', 'code', 'name', 'description', 'is_active', 'created_at');

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        return Excel::download(new DesignationExport($query), 'designations.xlsx');
    }

    public function status(Request $request)
    {
        $request->validate([
            'id' => ['required', 'integer', 'exists:designations,id'],
            'status' => ['required', 'boolean'],
        ]);

        $designation = Designation::findOrFail($request->id);
        $designation->is_active = $request->status;
        $designation->save();

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }
}
