<?php

namespace App\Http\Controllers;

use App\Exports\DocumentTypeExport;
use App\Http\Requests\StoreDocumentTypeRequest;
use App\Http\Requests\UpdateDocumentTypeRequest;
use App\Models\DocumentType;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class DocumentTypeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('document-types.view'), ['index', 'show', 'export']),
            new Middleware(PermissionMiddleware::using('document-types.create'), ['create', 'store']),
            new Middleware(PermissionMiddleware::using('document-types.edit'), ['edit', 'update', 'status']),
            new Middleware(PermissionMiddleware::using('document-types.delete'), ['destroy']),
        ];
    }

    public function index()
    {
        if (request()->ajax()) {
            $query = DocumentType::select([
                'id',
                'code',
                'name',
                'applicable_for',
                'is_expiry_required',
                'is_active',
                'created_at',
            ])->orderBy('created_at', 'desc');

            if (request()->filled('status') && in_array(request('status'), ['0', '1'], true)) {
                $query->where('is_active', request('status'));
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
                })
                ->addColumn('applies_to', function ($row) {
                    return $this->formatApplicableFor($row->applicable_for);
                })
                ->addColumn('expiry', function ($row) {
                    return $row->is_expiry_required
                        ? '<span class="status-green">Required</span>'
                        : '<span class="status-red">Not Required</span>';
                })
                ->addColumn('status', function ($row) {
                    return $row->is_active
                        ? '<span class="status-green">Active</span>'
                        : '<span class="status-red">Inactive</span>';
                })
                ->addColumn('action', function ($row) {
                    return view('document-type.partials.action', compact('row'))->render();
                })
                ->rawColumns(['action', 'expiry', 'status', 'checkbox'])
                ->make(true);
        }

        return view('document-type.index');
    }

    public function create(Request $request)
    {
        if ($request->id) {
            $record = DocumentType::findOrFail($request->id);

            return response()->json([
                'html' => view('document-type.form', compact('record'))->render(),
                'title' => 'Update Document Type',
            ]);
        }

        $generatedCode = generate_code('Document Type Module', ((int) DocumentType::max('id')) + 1, 3, 'DOCT');

        return response()->json([
            'html' => view('document-type.form', compact('generatedCode'))->render(),
            'title' => 'Add Document Type',
        ]);
    }

    public function store(StoreDocumentTypeRequest $request)
    {
        $documentType = DocumentType::create($request->validated());
        $documentType->code = generate_code('Document Type Module', $documentType->id, 3, 'DOCT');
        $documentType->save();

        return response()->json([
            'success' => true,
            'message' => 'Document type created successfully.',
            'data' => $documentType,
        ], 201);
    }

    public function show(DocumentType $documentType) {}

    public function edit(DocumentType $documentType) {}

    public function update(UpdateDocumentTypeRequest $request, DocumentType $documentType)
    {
        $documentType->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Document type updated successfully.',
            'data' => $documentType->fresh(),
        ]);
    }

    public function destroy(DocumentType $documentType)
    {
        $documentType->delete();

        return response()->json([
            'success' => true,
            'message' => 'Document type deleted successfully.',
        ]);
    }

    public function export(Request $request)
    {
        $ids = $request->input('ids', []);
        $query = DocumentType::select('code', 'name', 'applicable_for', 'is_expiry_required', 'is_active', 'created_at');

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        return Excel::download(new DocumentTypeExport($query), 'document-types.xlsx');
    }

    public function status(Request $request)
    {
        $request->validate([
            'id' => ['required', 'integer', 'exists:document_types,id'],
            'status' => ['required', 'boolean'],
        ]);

        $documentType = DocumentType::findOrFail($request->id);
        $documentType->is_active = $request->status;
        $documentType->save();

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }

    private function formatApplicableFor(?string $applicableFor): string
    {
        return match ($applicableFor) {
            'driver' => 'Driver',
            'vehicle' => 'Vehicle',
            'oem' => 'OEM',
            'supervisor' => 'Supervisor',
            'controller' => 'Controller',
            default => '-',
        };
    }
}
