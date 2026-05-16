<?php

namespace App\Http\Controllers;

use App\Exports\HrmsDocumentTypeExport;
use App\Http\Requests\StoreHrmsDocumentTypeRequest;
use App\Http\Requests\UpdateHrmsDocumentTypeRequest;
use App\Models\HrmsDocumentType;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class HrmsDocumentTypeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('hrms-document-types.view'), ['index', 'show', 'export']),
            new Middleware(PermissionMiddleware::using('hrms-document-types.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('hrms-document-types.edit'), ['edit', 'update', 'status']),
            new Middleware(PermissionMiddleware::using('hrms-document-types.delete'), ['destroy']),
        ];
    }

    public function index()
    {
        if (request()->ajax()) {
            $query = HrmsDocumentType::select([
                'id',
                'code',
                'name',
                'category',
                'applicable_for',
                'is_mandatory',
                'is_expiry_required',
                'is_active',
                'created_at',
            ])->orderBy('created_at', 'desc');

            if (request()->filled('category')) {
                $query->where('category', request('category'));
            }

            if (request()->filled('applicable_for')) {
                $query->where('applicable_for', request('applicable_for'));
            }

            if (request()->filled('mandatory') && in_array(request('mandatory'), ['0', '1'], true)) {
                $query->where('is_mandatory', request('mandatory'));
            }

            if (request()->filled('status') && in_array(request('status'), ['0', '1'], true)) {
                $query->where('is_active', request('status'));
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
                })
                ->addColumn('mandatory', function ($row) {
                    return $row->is_mandatory
                        ? '<span class="status-green">Yes</span>'
                        : '<span class="status-red">No</span>';
                })
                ->addColumn('expiry', function ($row) {
                    return $row->is_expiry_required
                        ? '<span class="status-green">Yes</span>'
                        : '<span class="status-red">No</span>';
                })
                ->addColumn('applicable_for_label', function ($row) {
                    return HrmsDocumentType::APPLICABLE_FOR[$row->applicable_for] ?? '-';
                })
                ->addColumn('status', function ($row) {
                    return $row->is_active
                        ? '<span class="status-green">Active</span>'
                        : '<span class="status-red">Inactive</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('hrms-document-type.partials.action', compact('row'))->render();
                })
                ->rawColumns(['action', 'mandatory', 'expiry', 'status', 'checkbox'])
                ->make(true);
        }

        $categories = HrmsDocumentType::CATEGORIES;
        $applicableFor = HrmsDocumentType::APPLICABLE_FOR;

        return view('hrms-document-type.index', compact('categories', 'applicableFor'));
    }

    public function create(Request $request)
    {
        $categories = HrmsDocumentType::CATEGORIES;
        $applicableFor = HrmsDocumentType::APPLICABLE_FOR;
        $allowedFileTypes = HrmsDocumentType::ALLOWED_FILE_TYPES;

        if ($request->id) {
            $record = HrmsDocumentType::findOrFail($request->id);

            return response()->json([
                'html' => view('hrms-document-type.form', compact('record', 'categories', 'applicableFor', 'allowedFileTypes'))->render(),
                'title' => 'Update Document Type',
            ]);
        }

        $generatedCode = generate_code('HRMS Document Type Module', ((int) HrmsDocumentType::max('id')) + 1, 3, 'HDT');

        return response()->json([
            'html' => view('hrms-document-type.form', compact('generatedCode', 'categories', 'applicableFor', 'allowedFileTypes'))->render(),
            'title' => 'Add Document Type',
        ]);
    }

    public function store(StoreHrmsDocumentTypeRequest $request)
    {
        $documentType = HrmsDocumentType::create($request->validated());
        $documentType->code = generate_code('HRMS Document Type Module', $documentType->id, 3, 'HDT');
        $documentType->save();

        return response()->json([
            'success' => true,
            'message' => 'Document type created successfully.',
            'data' => $documentType,
        ], 201);
    }

    public function show(HrmsDocumentType $hrmsDocumentType) {}

    public function edit(HrmsDocumentType $hrmsDocumentType) {}

    public function update(UpdateHrmsDocumentTypeRequest $request, HrmsDocumentType $hrmsDocumentType)
    {
        $hrmsDocumentType->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Document type updated successfully.',
            'data' => $hrmsDocumentType->fresh(),
        ]);
    }

    public function destroy(HrmsDocumentType $hrmsDocumentType)
    {
        $hrmsDocumentType->delete();

        return response()->json([
            'success' => true,
            'message' => 'Document type deleted successfully.',
        ]);
    }

    public function export(Request $request)
    {
        $ids = $request->input('ids', []);
        $query = HrmsDocumentType::select(
            'code',
            'name',
            'category',
            'applicable_for',
            'allowed_file_types',
            'is_mandatory',
            'is_expiry_required',
            'is_active',
            'description',
            'created_at'
        );

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        return Excel::download(new HrmsDocumentTypeExport($query), 'hrms-document-types.xlsx');
    }

    public function status(Request $request)
    {
        $request->validate([
            'id' => ['required', 'integer', 'exists:hrms_document_types,id'],
            'status' => ['required', 'boolean'],
        ]);

        $documentType = HrmsDocumentType::findOrFail($request->id);
        $documentType->is_active = $request->status;
        $documentType->save();

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }
}
