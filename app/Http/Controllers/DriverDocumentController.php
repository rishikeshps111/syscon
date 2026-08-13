<?php

namespace App\Http\Controllers;

use App\Models\DriverDocument;
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

class DriverDocumentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('driver-management.view'), ['index', 'preview', 'download']),
            new Middleware(PermissionMiddleware::using('driver-management.edit'), ['store', 'destroy']),
        ];
    }

    public function index(Request $request, User $driver)
    {
        abort_unless($driver->hasRole('Driver'), 404);
        $driver->load(['driverProfile.depot', 'driverProfile.branchLocation']);

        $documentTypes = $this->documentTypes();

        if ($request->ajax()) {
            $query = DriverDocument::with('documentType')
                ->where('user_id', $driver->id)
                ->latest();

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('type', fn($row) => $row->documentType?->name ?? '-')
                ->addColumn('expiry_date', fn($row) => $row->expiry_date?->format('d-m-Y') ?? '-')
                ->addColumn('status', function ($row) {
                    return $row->is_verified
                        ? '<span class="status-green">Verified</span>'
                        : '<span class="status-red">Not Verified</span>';
                })
                ->addColumn('action', function ($row) {
                    $previewUrl = route('driver-documents.preview', $row->id);
                    $downloadUrl = route('driver-documents.download', $row->id);
                    $deleteButton = '';

                    if (auth()->user()->can('driver-management.edit')) {
                        $editButton = '<button type="button" class="btn-edit edit-document" data-id="' . $row->id . '" data-action="' . e(route('driver-documents.update', $row->id)) . '" data-type="' . $row->hrms_document_type_id . '" data-expiry="' . e($row->expiry_date?->format('Y-m-d') ?? '') . '" data-verified="' . ($row->is_verified ? 1 : 0) . '" data-preview="' . e($previewUrl) . '" title="Edit"><i class="fa-solid fa-pen"></i></button>';
                        $deleteButton = '<button type="button" class="btn-delete" onclick="deleteDocument(' . $row->id . ')" title="Delete"><i class="fa-solid fa-trash"></i></button>';
                    } else {
                        $editButton = '';
                    }

                    $viewButton = auth()->user()->can('driver-management.view')
                        ? '<button type="button" class="btn-edit view-document" data-bs-toggle="modal" data-bs-target="#viewDoc" data-preview="' . e($previewUrl) . '" data-download="' . e($downloadUrl) . '" title="View"><i class="fa-solid fa-eye"></i></button>'
                        : '';

                    return '<div class="action-btns justify-content-center">' . $viewButton . $editButton . $deleteButton . '</div>';
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return view('driver-management.documents.index', compact('driver', 'documentTypes'));
    }

    public function store(Request $request, User $driver)
    {
        abort_unless($driver->hasRole('Driver'), 404);

        $documentTypeIds = $this->documentTypes()->pluck('id')->all();
        $validated = $request->validate([
            'hrms_document_type_id' => ['required', Rule::in($documentTypeIds), Rule::unique('driver_documents', 'hrms_document_type_id')->where(fn($query) => $query->where('user_id', $driver->id))],
            'expiry_date' => ['nullable', 'date'],
            'document_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
            'is_verified' => ['nullable', 'boolean'],
        ], [
            'hrms_document_type_id.unique' => 'This document is already added.',
        ]);

        $documentType = HrmsDocumentType::findOrFail($validated['hrms_document_type_id']);

        if ($documentType->is_expiry_required && empty($validated['expiry_date'])) {
            throw ValidationException::withMessages([
                'expiry_date' => 'Expiry date is required for this document type.',
            ]);
        }

        $this->validateAllowedFileType($request, $documentType);

        $file = $request->file('document_file');
        $path = $file->store('driver-documents/' . $driver->id, 'public');

        $document = DriverDocument::create([
            'user_id' => $driver->id,
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

    public function download(DriverDocument $driverDocument)
    {
        abort_unless($driverDocument->driver?->hasRole('Driver'), 404);
        abort_unless(Storage::disk('public')->exists($driverDocument->file_path), 404);

        return Storage::disk('public')->download(
            $driverDocument->file_path,
            $driverDocument->original_name ?: basename($driverDocument->file_path)
        );
    }

    public function update(Request $request, DriverDocument $driverDocument)
    {
        abort_unless($driverDocument->driver?->hasRole('Driver'), 404);

        $documentTypeIds = $this->documentTypes()->pluck('id')->all();
        $validated = $request->validate([
            'hrms_document_type_id' => ['required', Rule::in($documentTypeIds), Rule::unique('driver_documents', 'hrms_document_type_id')->where(fn($query) => $query->where('user_id', $driverDocument->user_id)->where('id', '<>', $driverDocument->id))],
            'expiry_date' => ['nullable', 'date'],
            'document_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
            'is_verified' => ['nullable', 'boolean'],
        ]);
        $documentType = HrmsDocumentType::findOrFail($validated['hrms_document_type_id']);
        if ($documentType->is_expiry_required && empty($validated['expiry_date'])) {
            throw ValidationException::withMessages(['expiry_date' => 'Expiry date is required for this document type.']);
        }
        if ($request->hasFile('document_file')) {
            $this->validateAllowedFileType($request, $documentType);
            Storage::disk('public')->delete($driverDocument->file_path);
            $file = $request->file('document_file');
            $driverDocument->file_path = $file->store('driver-documents/' . $driverDocument->user_id, 'public');
            $driverDocument->original_name = $file->getClientOriginalName();
        }
        $driverDocument->hrms_document_type_id = $validated['hrms_document_type_id'];
        $driverDocument->expiry_date = $validated['expiry_date'] ?? null;
        $driverDocument->is_verified = (bool) ($validated['is_verified'] ?? false);
        $driverDocument->save();
        return response()->json(['success' => true, 'message' => 'Document updated successfully.']);
    }

    public function preview(DriverDocument $driverDocument)
    {
        abort_unless($driverDocument->driver?->hasRole('Driver'), 404);
        abort_unless(Storage::disk('public')->exists($driverDocument->file_path), 404);

        return response()->file(Storage::disk('public')->path($driverDocument->file_path));
    }

    public function destroy(DriverDocument $driverDocument)
    {
        abort_unless($driverDocument->driver?->hasRole('Driver'), 404);

        if ($driverDocument->file_path) {
            Storage::disk('public')->delete($driverDocument->file_path);
        }

        $driverDocument->delete();

        return response()->json([
            'success' => true,
            'message' => 'Document deleted successfully.',
        ]);
    }

    private function documentTypes()
    {
        return HrmsDocumentType::where('is_active', true)
            ->whereIn('applicable_for', ['all', 'driver'])
            ->orderBy('name')
            ->get();
    }

    private function validateAllowedFileType(Request $request, HrmsDocumentType $documentType): void
    {
        if (! $documentType->allowed_file_types) {
            return;
        }

        $extension = strtolower($request->file('document_file')->getClientOriginalExtension());
        $allowed = match ($documentType->allowed_file_types) {
            'jpg' => ['jpg', 'jpeg'],
            'doc' => ['doc', 'docx'],
            default => [$documentType->allowed_file_types],
        };

        if (! in_array($extension, $allowed, true)) {
            throw ValidationException::withMessages([
                'document_file' => 'Please upload a ' . strtoupper($documentType->allowed_file_types) . ' file for this document type.',
            ]);
        }
    }
}
