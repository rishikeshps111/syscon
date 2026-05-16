<?php

namespace App\Http\Controllers;

use App\Exports\DepartmentExport;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class DepartmentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('departments.view'), ['index', 'show', 'export']),
            new Middleware(PermissionMiddleware::using('departments.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('departments.edit'), ['edit', 'update', 'status']),
            new Middleware(PermissionMiddleware::using('departments.delete'), ['destroy']),
        ];
    }

    public function index()
    {
        if (request()->ajax()) {
            $query = Department::select(['id', 'code', 'name', 'is_active', 'created_at'])
                ->orderBy('created_at', 'desc');

            if (request()->filled('status') && in_array(request('status'), ['0', '1'], true)) {
                $query->where('is_active', request('status'));
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
                })
                ->addColumn('status', function ($row) {
                    return $row->is_active
                        ? '<span class="status-green">Active</span>'
                        : '<span class="status-red">Inactive</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('department.partials.action', compact('row'))->render();
                })
                ->rawColumns(['action', 'status', 'checkbox'])
                ->make(true);
        }

        return view('department.index');
    }

    public function create(Request $request)
    {
        if ($request->id) {
            $record = Department::findOrFail($request->id);

            return response()->json([
                'html' => view('department.form', compact('record'))->render(),
                'title' => 'Update Department',
            ]);
        }

        $generatedCode = generate_code('Department Module', ((int) Department::max('id')) + 1, 3, 'DPT');

        return response()->json([
            'html' => view('department.form', compact('generatedCode'))->render(),
            'title' => 'Add Department',
        ]);
    }

    public function store(StoreDepartmentRequest $request)
    {
        $department = Department::create($request->validated());
        $department->code = generate_code('Department Module', $department->id, 3, 'DPT');
        $department->save();

        return response()->json([
            'success' => true,
            'message' => 'Department created successfully.',
            'data' => $department,
        ], 201);
    }

    public function show(Department $department) {}

    public function edit(Department $department) {}

    public function update(UpdateDepartmentRequest $request, Department $department)
    {
        $department->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Department updated successfully.',
            'data' => $department->fresh(),
        ]);
    }

    public function destroy(Department $department)
    {
        $department->delete();

        return response()->json([
            'success' => true,
            'message' => 'Department deleted successfully.',
        ]);
    }

    public function export(Request $request)
    {
        $ids = $request->input('ids', []);
        $query = Department::select('code', 'name', 'is_active', 'created_at');

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        return Excel::download(new DepartmentExport($query), 'departments.xlsx');
    }

    public function status(Request $request)
    {
        $request->validate([
            'id' => ['required', 'integer', 'exists:departments,id'],
            'status' => ['required', 'boolean'],
        ]);

        $department = Department::findOrFail($request->id);
        $department->is_active = $request->status;
        $department->save();

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }
}
