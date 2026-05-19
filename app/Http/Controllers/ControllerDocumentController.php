<?php

namespace App\Http\Controllers;

use App\Models\ControllerDocument;
use App\Models\HrmsDocumentType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class ControllerDocumentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('controller-management.view'), ['index', 'preview', 'download']),
            new Middleware(PermissionMiddleware::using('controller-management.edit'), ['store', 'destroy']),
        ];
    }

    public function index(Request $request, User $controller)
    {
        abort_unless($controller->hasRole('Controller'), 404);
        $controller->load('controllerProfile.depot');

        $documentTypes = $this->documentTypes();

        if ($request->ajax()) {
            $query = ControllerDocument::with('documentType')
                ->where('user_id', $controller->id)
                ->latest();

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('type', fn ($row) => $row->documentType?->name ?? '-')
                ->addColumn('expiry_date', fn ($row) => $row->expiry_date?->format('d-m-Y') ?? '-')
                ->addColumn('status', fn ($row) => $row->is_verified
                    ? '<span class="status-green">Verified</span>'
                    : '<span class="status-red">Not Verified</span>')
                ->addColumn('action', function ($row) {
                    $previewUrl = route('controller-documents.preview', $row->id);
                    $downloadUrl = route('controller-documents.download', $row->id);
                    $deleteButton = '';

                    if (auth()->user()->can('controller-management.edit')) {
                        $deleteButton = '<button type="button" class="btn-delete" onclick="deleteDocument(' . $row->id . ')" title="Delete"><i class="fa-solid fa-trash"></i></button>';
                    }

                    $viewButton = auth()->user()->can('controller-management.view')
                        ? '<button type="button" class="btn-edit view-document" data-bs-toggle="modal" data-bs-target="#viewDoc" data-preview="' . e($previewUrl) . '" data-download="' . e($downloadUrl) . '" title="View"><i class="fa-solid fa-eye"></i></button>'
                        : '';

                    return '<div class="action-btns justify-content-center">' . $viewButton . $deleteButton . '</div>';
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('controller-management.documents.index', compact('controller', 'documentTypes'));
    }

    public function store(Request $request, User $controller)
    {
        abort_unless($controller->hasRole('Controller'), 404);

        $documentTypeIds = $this->documentTypes()->pluck('id')->all();
        $validated = $request->validate([
            'hrms_document_type_id' => ['required', Rule::in($documentTypeIds)],
            'expiry_date' => ['nullable', 'date'],
            'document_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
            'is_verified' => ['nullable', 'boolean'],
        ]);

        $documentType = HrmsDocumentType::findOrFail($validated['hrms_document_type_id']);

        if ($documentType->is_expiry_required && empty($validated['expiry_date'])) {
            throw ValidationException::withMessages([
                'expiry_date' => 'Expiry date is required for this document type.',
            ]);
        }

        $this->validateAllowedFileType($request, $documentType);

        $file = $request->file('document_file');
        $path = $file->store('controller-documents/' . $controller->id, 'public');

        $document = ControllerDocument::create([
            'user_id' => $controller->id,
            'hrms_document_type_id' => $validated['hrms_document_type_id'],
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

    public function download(ControllerDocument $controllerDocument)
    {
        abort_unless($controllerDocument->controller?->hasRole('Controller'), 404);
        abort_unless(Storage::disk('public')->exists($controllerDocument->file_path), 404);

        return Storage::disk('public')->download(
            $controllerDocument->file_path,
            $controllerDocument->original_name ?: basename($controllerDocument->file_path)
        );
    }

    public function preview(ControllerDocument $controllerDocument)
    {
        abort_unless($controllerDocument->controller?->hasRole('Controller'), 404);
        abort_unless(Storage::disk('public')->exists($controllerDocument->file_path), 404);

        return response()->file(Storage::disk('public')->path($controllerDocument->file_path));
    }

    public function destroy(ControllerDocument $controllerDocument)
    {
        abort_unless($controllerDocument->controller?->hasRole('Controller'), 404);

        if ($controllerDocument->file_path) {
            Storage::disk('public')->delete($controllerDocument->file_path);
        }

        $controllerDocument->delete();

        return response()->json([
            'success' => true,
            'message' => 'Document deleted successfully.',
        ]);
    }

    private function documentTypes()
    {
        return HrmsDocumentType::where('is_active', true)
            ->whereIn('applicable_for', ['all', 'controller'])
            ->orderBy('name')
            ->get();
    }

    private function validateAllowedFileType(Request $request, HrmsDocumentType $documentType): void
    {
        if (! $documentType->allowed_file_types) {
            return;
        }

        $extension = strtolower($request->file('document_file')->getClientOriginalExtension());
        $allowed = $documentType->allowed_file_types === 'jpg'
            ? ['jpg', 'jpeg']
            : [$documentType->allowed_file_types];

        if (! in_array($extension, $allowed, true)) {
            throw ValidationException::withMessages([
                'document_file' => 'Please upload a ' . strtoupper($documentType->allowed_file_types) . ' file for this document type.',
            ]);
        }
    }
}
