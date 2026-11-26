<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\StaffResource;
use App\Models\Designation;
use App\Models\GeneratedHrLetter;
use App\Models\StaffDocument;
use App\Models\User;
use App\Support\HrLetterPdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeStaffToken($request);
        $filters = $request->validate([
            'search_text' => ['nullable', 'string', 'max:255'],
            'designation_id' => ['nullable', 'integer', 'exists:designations,id'],
            'employment_type' => ['nullable', Rule::in(['full_time', 'part_time', 'contract'])],
            'date_of_joining' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', Rule::in(['0', '1', 0, 1])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = $this->depotStaffQuery($request)
            ->with(['staffProfile.designation', 'staffProfile.reportingTo', 'staffProfile.depot',
                'staffProfile.state', 'staffProfile.district', 'staffProfile.location']);

        if (filled($filters['search_text'] ?? null)) {
            $search = trim($filters['search_text']);
            $query->where(fn (Builder $users) => $users
                ->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('ref_code', 'like', "%{$search}%"));
        }

        $profileFilters = collect($filters)->only(['designation_id', 'employment_type', 'date_of_joining'])->all();
        if ($profileFilters !== []) {
            $query->whereHas('staffProfile', function (Builder $profile) use ($profileFilters): void {
                if (isset($profileFilters['designation_id'])) {
                    $profile->where('designation_id', $profileFilters['designation_id']);
                }
                if (isset($profileFilters['employment_type'])) {
                    $profile->where('employment_type', $profileFilters['employment_type']);
                }
                if (isset($profileFilters['date_of_joining'])) {
                    $profile->whereDate('date_of_joining', $profileFilters['date_of_joining']);
                }
            });
        }
        if (isset($filters['status'])) {
            $query->where('is_active', (bool) $filters['status']);
        }

        return StaffResource::collection($query->orderByDesc('users.created_at')->orderByDesc('users.id')
            ->paginate($filters['per_page'] ?? 10));
    }

    public function designations(Request $request): JsonResponse
    {
        $this->authorizeStaffToken($request);
        $depotId = $request->user()->staffProfile?->depot_id;
        if (! $depotId) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $designations = Designation::query()
            ->whereHas('staffProfiles', fn (Builder $profiles) => $profiles
                ->where('depot_id', $depotId)
                ->whereHas('user', fn (Builder $users) => $users->role('Staff')))
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['success' => true, 'data' => $designations]);
    }

    public function show(Request $request, User $staff): StaffResource
    {
        $this->authorizeStaffToken($request);
        $record = $this->depotStaffQuery($request)
            ->with(['staffProfile.designation', 'staffProfile.reportingTo', 'staffProfile.depot',
                'staffProfile.state', 'staffProfile.district', 'staffProfile.location'])
            ->findOrFail($staff->id);

        return new StaffResource($record);
    }

    public function documents(Request $request, User $staff): JsonResponse
    {
        $this->accessibleStaff($request, $staff);

        $documents = StaffDocument::query()->with('documentType:id,name')
            ->where('user_id', $staff->id)->latest('id')->get()
            ->map(fn (StaffDocument $document) => $this->documentData($document));

        return response()->json(['success' => true, 'data' => $documents]);
    }

    public function previewDocument(Request $request, User $staff, StaffDocument $document)
    {
        $this->accessibleDocument($request, $staff, $document);

        return response()->file(Storage::disk('public')->path($document->file_path));
    }

    public function downloadDocument(Request $request, User $staff, StaffDocument $document)
    {
        $this->accessibleDocument($request, $staff, $document);

        return Storage::disk('public')->download(
            $document->file_path,
            $document->original_name ?: basename($document->file_path)
        );
    }

    public function letters(Request $request, User $staff): JsonResponse
    {
        $this->accessibleStaff($request, $staff);

        $letters = GeneratedHrLetter::query()->where('user_id', $staff->id)
            ->latest('generated_at')->latest('id')->get()
            ->map(fn (GeneratedHrLetter $letter) => $this->letterData($letter));

        return response()->json(['success' => true, 'data' => $letters]);
    }

    public function showLetter(Request $request, User $staff, GeneratedHrLetter $letter): JsonResponse
    {
        $this->accessibleLetter($request, $staff, $letter);

        return response()->json(['success' => true, 'data' => $this->letterData($letter) + [
            'content' => $letter->content,
            'header_logo' => $letter->header_logo
                ? asset('storage/'.$letter->header_logo)
                : null,            'header_address' => $letter->header_address,
            'footer_content' => $letter->footer_content,
        ]]);
    }

    public function previewLetter(Request $request, User $staff, GeneratedHrLetter $letter, HrLetterPdf $pdf)
    {
        $this->accessibleLetter($request, $staff, $letter);

        return $pdf->response($letter, 'inline');
    }

    public function downloadLetter(Request $request, User $staff, GeneratedHrLetter $letter, HrLetterPdf $pdf)
    {
        $this->accessibleLetter($request, $staff, $letter);

        return $pdf->response($letter);
    }

    private function accessibleStaff(Request $request, User $staff): void
    {
        $this->authorizeStaffToken($request);
        $this->depotStaffQuery($request)->findOrFail($staff->id);
    }

    private function accessibleDocument(Request $request, User $staff, StaffDocument $document): void
    {
        $this->accessibleStaff($request, $staff);
        abort_unless($document->user_id === $staff->id && $document->file_path
            && Storage::disk('public')->exists($document->file_path), 404);
    }

    private function accessibleLetter(Request $request, User $staff, GeneratedHrLetter $letter): void
    {
        $this->accessibleStaff($request, $staff);
        abort_unless($letter->user_id === $staff->id, 404);
    }

    private function documentData(StaffDocument $document): array
    {
        return [
            'id' => $document->id,
            'document_type' => $document->documentType?->name,
            'original_name' => $document->original_name,
            'is_verified' => $document->is_verified,
            'expiry_date' => $document->expiry_date?->format('d M Y'),
        ];
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

    private function depotStaffQuery(Request $request): Builder
    {
        $depotId = $request->user()->staffProfile?->depot_id;

        if (! $depotId) {
            return User::query()->whereRaw('1 = 0');
        }

        return User::query()->role('Staff')
            ->whereHas('staffProfile', fn (Builder $profile) => $profile->where('depot_id', $depotId));
    }

    private function authorizeStaffToken(Request $request): void
    {
        abort_unless($request->user()->hasRole('Staff')
            && $request->user()->currentAccessToken()?->can('type:staff')
            && $request->user()->is_active
            && $request->user()->staff_api_enabled, 403);
    }
}
