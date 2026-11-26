<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DriverManagementResource;
use App\Models\DriverDocument;
use App\Models\DriverProfile;
use App\Models\GeneratedHrLetter;
use App\Models\User;
use App\Support\HrLetterPdf;
use App\Support\SimpleQrCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DriverManagementController extends Controller
{
    private const PROFILE_RELATIONS = [
        'driverProfile.designation', 'driverProfile.depot', 'driverProfile.branchLocation',
        'driverProfile.state', 'driverProfile.district', 'driverProfile.location',
    ];

    public function index(Request $request)
    {
        $this->authorizeStaff($request);
        $filters = $request->validate([
            'search_text' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['nullable', Rule::in(array_keys(DriverProfile::EMPLOYMENT_TYPES))],
            'license_type' => ['nullable', Rule::in(array_keys(DriverProfile::LICENSE_TYPES))],
            'verification_status' => ['nullable', Rule::in(array_keys(DriverProfile::VERIFICATION_STATUSES))],
            'status' => ['nullable', Rule::in(['0', '1', 0, 1])],
            'expiry_filter' => ['nullable', Rule::in(['license_expired', 'license_expiring', 'medical_expiring'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = $this->depotDrivers($request)->with(self::PROFILE_RELATIONS);

        if (filled($filters['search_text'] ?? null)) {
            $search = trim($filters['search_text']);
            $query->where(fn (Builder $users) => $users
                ->where('users.code', 'like', "%{$search}%")
                ->orWhere('users.ref_code', 'like', "%{$search}%")
                ->orWhere('users.name', 'like', "%{$search}%")
                ->orWhere('users.phone', 'like', "%{$search}%"));
        }

        foreach (['employment_type', 'license_type', 'verification_status'] as $field) {
            if (isset($filters[$field])) {
                $query->whereHas('driverProfile', fn (Builder $profile) => $profile->where($field, $filters[$field]));
            }
        }
        if (isset($filters['status'])) {
            $query->where('users.is_active', (bool) $filters['status']);
        }

        $today = today()->toDateString();
        $end = today()->addMonth()->toDateString();
        match ($filters['expiry_filter'] ?? null) {
            'license_expired' => $query->whereHas('driverProfile', fn (Builder $profile) => $profile->expiredLicense()),
            'license_expiring' => $query->whereHas('driverProfile', fn (Builder $profile) => $profile
                ->whereDate('expiry_date', '>=', $today)->whereDate('expiry_date', '<=', $end)),
            'medical_expiring' => $query->whereHas('driverProfile', fn (Builder $profile) => $profile
                ->whereDate('medical_fitness_expiry', '>=', $today)->whereDate('medical_fitness_expiry', '<=', $end)),
            default => null,
        };

        return DriverManagementResource::collection($query
            ->orderByDesc('users.created_at')->orderByDesc('users.id')
            ->paginate($filters['per_page'] ?? 10));
    }

    public function filterOptions(Request $request): JsonResponse
    {
        $this->authorizeStaff($request);

        return response()->json(['success' => true, 'data' => [
            'employment_type' => DriverProfile::EMPLOYMENT_TYPES,
            'license_type' => DriverProfile::LICENSE_TYPES,
            'verification_status' => DriverProfile::VERIFICATION_STATUSES,
            'status' => ['1' => 'Active', '0' => 'Inactive'],
            'expiry_filter' => [
                'license_expired' => 'License Expired',
                'license_expiring' => 'License Expiring',
                'medical_expiring' => 'Medical Expiring',
            ],
        ]]);
    }

    public function show(Request $request, User $driver): DriverManagementResource
    {
        return new DriverManagementResource($this->accessibleDriver($request, $driver)
            ->load(self::PROFILE_RELATIONS));
    }

    public function documents(Request $request, User $driver): JsonResponse
    {
        $this->accessibleDriver($request, $driver);
        $documents = DriverDocument::query()->with('documentType:id,name')
            ->where('user_id', $driver->id)->latest('id')->get()
            ->map(fn (DriverDocument $document) => [
                'id' => $document->id,
                'document_type' => $document->documentType?->name,
                'original_name' => $document->original_name,
                'is_verified' => $document->is_verified,
                'expiry_date' => $document->expiry_date?->format('d M Y'),
            ]);

        return response()->json(['success' => true, 'data' => $documents]);
    }

    public function previewDocument(Request $request, User $driver, DriverDocument $document)
    {
        $this->accessibleDocument($request, $driver, $document);

        return response()->file(Storage::disk('public')->path($document->file_path));
    }

    public function downloadDocument(Request $request, User $driver, DriverDocument $document)
    {
        $this->accessibleDocument($request, $driver, $document);

        return Storage::disk('public')->download($document->file_path,
            $document->original_name ?: basename($document->file_path));
    }

    public function letters(Request $request, User $driver): JsonResponse
    {
        $this->accessibleDriver($request, $driver);
        $letters = GeneratedHrLetter::query()->where('user_id', $driver->id)
            ->latest('generated_at')->latest('id')->get()
            ->map(fn (GeneratedHrLetter $letter) => $this->letterData($letter));

        return response()->json(['success' => true, 'data' => $letters]);
    }

    public function showLetter(Request $request, User $driver, GeneratedHrLetter $letter): JsonResponse
    {
        $this->accessibleLetter($request, $driver, $letter);

        return response()->json(['success' => true, 'data' => $this->letterData($letter) + [
            'content' => $letter->content,
            'header_logo' => $letter->header_logo,
            'header_address' => $letter->header_address,
            'footer_content' => $letter->footer_content,
        ]]);
    }

    public function previewLetter(Request $request, User $driver, GeneratedHrLetter $letter, HrLetterPdf $pdf)
    {
        $this->accessibleLetter($request, $driver, $letter);

        return $pdf->response($letter, 'inline');
    }

    public function downloadLetter(Request $request, User $driver, GeneratedHrLetter $letter, HrLetterPdf $pdf)
    {
        $this->accessibleLetter($request, $driver, $letter);

        return $pdf->response($letter);
    }

    public function qr(Request $request, User $driver): JsonResponse
    {
        $this->accessibleDriver($request, $driver);
        $code = $this->qrCode($driver);

        return response()->json(['success' => true, 'code' => $code,
            'name' => $driver->name, 'svg' => SimpleQrCode::svg($code, 10)]);
    }

    public function downloadQr(Request $request, User $driver)
    {
        $this->accessibleDriver($request, $driver);
        $code = $this->qrCode($driver);

        return response(SimpleQrCode::svg($code, 10))
            ->header('Content-Type', 'image/svg+xml')
            ->header('Content-Disposition', 'attachment; filename="'.$code.'-qr.svg"');
    }

    private function qrCode(User $driver): string
    {
        abort_if(blank($driver->code), 422, 'Driver code is not available.');
        abort_if(strlen($driver->code) > 17 || ! mb_check_encoding($driver->code, 'ASCII'),
            422, 'Driver code cannot be encoded as a QR code.');

        return $driver->code;
    }

    private function letterData(GeneratedHrLetter $letter): array
    {
        return [
            'id' => $letter->id,
            'letter_number' => $letter->letter_number,
            'entity_type' => $letter->entity_type,
            'language' => $letter->language,
            'subject' => $letter->subject,
            'generated_at' => $letter->generated_at?->toIso8601String(),
        ];
    }

    private function accessibleDocument(Request $request, User $driver, DriverDocument $document): void
    {
        $this->accessibleDriver($request, $driver);
        abort_unless($document->user_id === $driver->id && $document->file_path
            && Storage::disk('public')->exists($document->file_path), 404);
    }

    private function accessibleLetter(Request $request, User $driver, GeneratedHrLetter $letter): void
    {
        $this->accessibleDriver($request, $driver);
        abort_unless($letter->user_id === $driver->id, 404);
    }

    private function accessibleDriver(Request $request, User $driver): User
    {
        $this->authorizeStaff($request);

        return $this->depotDrivers($request)->findOrFail($driver->id);
    }

    private function depotDrivers(Request $request): Builder
    {
        $depotId = $request->user()->staffProfile?->depot_id;
        if (! $depotId) {
            return User::query()->whereRaw('1 = 0');
        }

        return User::query()->role('Driver')
            ->whereHas('driverProfile', fn (Builder $profile) => $profile->where('depot_id', $depotId));
    }

    private function authorizeStaff(Request $request): void
    {
        abort_unless($request->user()->hasRole('Staff')
            && $request->user()->currentAccessToken()?->can('type:staff')
            && $request->user()->is_active
            && $request->user()->staff_api_enabled, 403);
    }
}
