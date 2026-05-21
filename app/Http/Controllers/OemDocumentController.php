<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use App\Models\Oem;
use App\Models\OemDocument;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class OemDocumentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('oems.view'), ['index', 'preview', 'download']),
            new Middleware(PermissionMiddleware::using('oems.edit'), ['store', 'destroy']),
        ];
    }

    public function index(Request $request, Oem $oem)
    {
        $oem->load(['state', 'primaryContact']);
        $documentTypes = $this->documentTypes();

        if ($request->ajax()) {
            $query = OemDocument::with('documentType')
                ->where('oem_id', $oem->id)
                ->latest();

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('type', fn ($row) => $row->documentType?->name ?? '-')
                ->addColumn('expiry_date', fn ($row) => $row->expiry_date?->format('d-m-Y') ?? '-')
                ->addColumn('status', fn ($row) => $row->is_verified
                    ? '<span class="status-green">Verified</span>'
                    : '<span class="status-red">Not Verified</span>')
                ->addColumn('action', function ($row) {
                    $previewUrl = route('oem-documents.preview', $row->id);
                    $downloadUrl = route('oem-documents.download', $row->id);
                    $deleteButton = '';

                    if (auth()->user()->can('oems.edit')) {
                        $deleteButton = '<button type="button" class="btn-delete" onclick="deleteDocument(' . $row->id . ')" title="Delete"><i class="fa-solid fa-trash"></i></button>';
                    }

                    $viewButton = auth()->user()->can('oems.view')
                        ? '<button type="button" class="btn-edit view-document" data-bs-toggle="modal" data-bs-target="#viewDoc" data-preview="' . e($previewUrl) . '" data-download="' . e($downloadUrl) . '" title="View"><i class="fa-solid fa-eye"></i></button>'
                        : '';

                    return '<div class="action-btns justify-content-center">' . $viewButton . $deleteButton . '</div>';
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('oem.documents.index', compact('oem', 'documentTypes'));
    }

    public function store(Request $request, Oem $oem)
    {
        $documentTypeIds = $this->documentTypes()->pluck('id')->all();
        $validated = $request->validate([
            'document_type_id' => ['required', Rule::in($documentTypeIds)],
            'expiry_date' => ['nullable', 'date'],
            'document_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
            'is_verified' => ['nullable', 'boolean'],
        ]);

        $documentType = DocumentType::findOrFail($validated['document_type_id']);

        if ($documentType->is_expiry_required && empty($validated['expiry_date'])) {
            throw ValidationException::withMessages([
                'expiry_date' => 'Expiry date is required for this document type.',
            ]);
        }

        $file = $request->file('document_file');
        $path = $file->store('oem-documents/' . $oem->id, 'public');

        $document = OemDocument::create([
            'oem_id' => $oem->id,
            'document_type_id' => $validated['document_type_id'],
            'expiry_date' => $validated['expiry_date'] ?? null,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'is_verified' => (bool) ($validated['is_verified'] ?? false),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Document added successfully.',
            'data' => $document,
        ], 201);
    }

    public function download(OemDocument $oemDocument)
    {
        abort_unless(Storage::disk('public')->exists($oemDocument->file_path), 404);

        return Storage::disk('public')->download(
            $oemDocument->file_path,
            $oemDocument->original_name ?: basename($oemDocument->file_path)
        );
    }

    public function preview(OemDocument $oemDocument)
    {
        abort_unless(Storage::disk('public')->exists($oemDocument->file_path), 404);

        return response()->file(Storage::disk('public')->path($oemDocument->file_path));
    }

    public function destroy(OemDocument $oemDocument)
    {
        if ($oemDocument->file_path) {
            Storage::disk('public')->delete($oemDocument->file_path);
        }

        $oemDocument->delete();

        return response()->json([
            'success' => true,
            'message' => 'Document deleted successfully.',
        ]);
    }

    private function documentTypes()
    {
        return DocumentType::where('is_active', true)
            ->where('applicable_for', 'oem')
            ->orderBy('name')
            ->get();
    }
}
