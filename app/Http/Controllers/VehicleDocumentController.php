<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Yajra\DataTables\Facades\DataTables;

class VehicleDocumentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware(PermissionMiddleware::using('vehicles.view'), ['index', 'preview', 'download']),
            new Middleware(PermissionMiddleware::using('vehicles.edit'), ['store', 'destroy']),
        ];
    }

    public function index(Request $request, Vehicle $vehicle)
    {
        $vehicle->load(['state', 'oem', 'depot', 'branch']);
        $documentTypes = $this->documentTypes();

        if ($request->ajax()) {
            $query = VehicleDocument::with('documentType')
                ->where('vehicle_id', $vehicle->id)
                ->latest();

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('type', fn ($row) => $row->documentType?->name ?? '-')
                ->addColumn('expiry_date', fn ($row) => $this->expiryBadge($row->expiry_date))
                ->addColumn('status', fn ($row) => $row->is_verified
                    ? '<span class="status-green">Verified</span>'
                    : '<span class="status-red">Not Verified</span>')
                ->addColumn('action', function ($row) {
                    $previewUrl = route('vehicle-documents.preview', $row->id);
                    $downloadUrl = route('vehicle-documents.download', $row->id);
                    $deleteButton = '';

                    if (auth()->user()->can('vehicles.edit')) {
                        $deleteButton = '<button type="button" class="btn-delete" onclick="deleteDocument(' . $row->id . ')" title="Delete"><i class="fa-solid fa-trash"></i></button>';
                    }

                    $viewButton = auth()->user()->can('vehicles.view')
                        ? '<button type="button" class="btn-edit view-document" data-bs-toggle="modal" data-bs-target="#viewDoc" data-preview="' . e($previewUrl) . '" data-download="' . e($downloadUrl) . '" title="View"><i class="fa-solid fa-eye"></i></button>'
                        : '';

                    return '<div class="action-btns justify-content-center">' . $viewButton . $deleteButton . '</div>';
                })
                ->rawColumns(['expiry_date', 'status', 'action'])
                ->make(true);
        }

        return view('vehicle.documents.index', compact('vehicle', 'documentTypes'));
    }

    public function store(Request $request, Vehicle $vehicle)
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
        $path = $file->store('vehicle-documents/' . $vehicle->id, 'public');

        $document = VehicleDocument::create([
            'vehicle_id' => $vehicle->id,
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

    public function download(VehicleDocument $vehicleDocument)
    {
        abort_unless(Storage::disk('public')->exists($vehicleDocument->file_path), 404);

        return Storage::disk('public')->download(
            $vehicleDocument->file_path,
            $vehicleDocument->original_name ?: basename($vehicleDocument->file_path)
        );
    }

    public function preview(VehicleDocument $vehicleDocument)
    {
        abort_unless(Storage::disk('public')->exists($vehicleDocument->file_path), 404);

        return response()->file(Storage::disk('public')->path($vehicleDocument->file_path));
    }

    public function destroy(VehicleDocument $vehicleDocument)
    {
        if ($vehicleDocument->file_path) {
            Storage::disk('public')->delete($vehicleDocument->file_path);
        }

        $vehicleDocument->delete();

        return response()->json([
            'success' => true,
            'message' => 'Document deleted successfully.',
        ]);
    }

    private function documentTypes()
    {
        return DocumentType::where('is_active', true)
            ->where('applicable_for', 'vehicle')
            ->orderBy('name')
            ->get();
    }

    private function expiryBadge($date): string
    {
        if (! $date) {
            return '<span class="text-muted">-</span>';
        }

        $label = $date->format('d-m-Y');

        if ($date->lt(today())) {
            return '<span class="status-red">' . $label . ' - Expired</span>';
        }

        if ($date->lte(now()->addDays(30))) {
            return '<span class="status-orange">' . $label . ' - Expiring Soon</span>';
        }

        return '<span class="status-green">' . $label . ' - Active</span>';
    }
}
