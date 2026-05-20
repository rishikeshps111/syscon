<?php

namespace App\Http\Controllers;

use App\Exports\ComplaintCategoryExport;
use App\Http\Requests\StoreComplaintCategoryRequest;
use App\Http\Requests\UpdateComplaintCategoryRequest;
use App\Models\ComplaintCategory;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class ComplaintCategoryController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('complaint-categories.view'), ['index', 'show', 'export']),
            new Middleware(PermissionMiddleware::using('complaint-categories.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('complaint-categories.edit'), ['edit', 'update', 'status']),
            new Middleware(PermissionMiddleware::using('complaint-categories.delete'), ['destroy']),
        ];
    }

    public function index()
    {
        if (request()->ajax()) {
            $query = ComplaintCategory::select(['id', 'code', 'name', 'is_active', 'created_at'])
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
                    return view('complaint-category.partials.action', compact('row'))->render();
                })
                ->rawColumns(['action', 'status', 'checkbox'])
                ->make(true);
        }

        return view('complaint-category.index');
    }

    public function create(Request $request)
    {
        if ($request->id) {
            $record = ComplaintCategory::findOrFail($request->id);

            return response()->json([
                'html' => view('complaint-category.form', compact('record'))->render(),
                'title' => 'Update Complaint Category',
            ]);
        }

        $generatedCode = generate_code('Complaint Category Module', ((int) ComplaintCategory::max('id')) + 1, 3, 'CC');

        return response()->json([
            'html' => view('complaint-category.form', compact('generatedCode'))->render(),
            'title' => 'Add Complaint Category',
        ]);
    }

    public function store(StoreComplaintCategoryRequest $request)
    {
        $complaintCategory = ComplaintCategory::create($request->validated());
        $complaintCategory->code = generate_code('Complaint Category Module', $complaintCategory->id, 3, 'CC');
        $complaintCategory->save();

        return response()->json([
            'success' => true,
            'message' => 'Complaint category created successfully.',
            'data' => $complaintCategory,
        ], 201);
    }

    public function show(ComplaintCategory $complaintCategory) {}

    public function edit(ComplaintCategory $complaintCategory) {}

    public function update(UpdateComplaintCategoryRequest $request, ComplaintCategory $complaintCategory)
    {
        $complaintCategory->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Complaint category updated successfully.',
            'data' => $complaintCategory->fresh(),
        ]);
    }

    public function destroy(ComplaintCategory $complaintCategory)
    {
        $complaintCategory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Complaint category deleted successfully.',
        ]);
    }

    public function export(Request $request)
    {
        $ids = $request->input('ids', []);
        $query = ComplaintCategory::select('code', 'name', 'is_active', 'created_at');

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        return Excel::download(new ComplaintCategoryExport($query), 'complaint-categories.xlsx');
    }

    public function status(Request $request)
    {
        $request->validate([
            'id' => ['required', 'integer', 'exists:complaint_categories,id'],
            'status' => ['required', 'boolean'],
        ]);

        $complaintCategory = ComplaintCategory::findOrFail($request->id);
        $complaintCategory->is_active = $request->status;
        $complaintCategory->save();

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }
}
