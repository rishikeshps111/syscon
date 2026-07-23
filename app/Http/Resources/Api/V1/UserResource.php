<?php

namespace App\Http\Resources\Api\V1;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'email' => $this->email,
            'country_code' => $this->country_code,
            'phone' => $this->phone,
            'full_phone' => $this->full_phone,
            'avatar_url' => $this->avatar_url,
            'is_active' => $this->is_active,
            'type' => $this->api_user_type,
            'roles' => $this->roles->pluck('name')->values(),
            'profile' => $this->profilePayload(),
        ];
    }

    private function profilePayload(): ?array
    {
        $type = $this->api_user_type;
        $profile = match ($type) {
            'driver' => $this->driverProfile,
            'controller' => $this->controllerProfile,
            'supervisor' => $this->supervisorProfile,
            default => null,
        };

        if (! $profile) {
            return null;
        }

        $data = $profile->toArray();

        foreach (['date_of_birth', 'date_of_joining', 'joining_date'] as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = $this->formatDate($profile->{$field});
            }
        }

        if (! empty($data['employment_type'])) {
            $data['employment_type'] = $this->readableValue($data['employment_type']);
        }

        $aadhaarDocument = $this->documentPayload($type, ['aadhaar', 'aadhar']);
        $data['current_depot'] = $this->depotPayload($profile->depot);
        $data['aadhaar_file_url'] = $aadhaarDocument['file_url'] ?? null;
        $data['aadhaar_document'] = $aadhaarDocument;

        if ($type === 'driver') {
            $licenceDocument = $this->documentPayload($type, ['licence', 'license']);
            $badgeDocument = $this->documentPayload($type, ['badge']);
            $medicalCertificateDocument = $this->documentPayload($type, ['medical certificate', 'medical fitness', 'medical']);

            if ($licenceDocument) {
                $licenceDocument['status'] = $this->documentExpiryStatus($licenceDocument['expiry_date']);
            }

            if ($badgeDocument) {
                $badgeDocument['status'] = $this->documentExpiryStatus($badgeDocument['expiry_date']);
            }

            $data['licence_file_url'] = $licenceDocument['file_url'] ?? null;
            $data['licence_document'] = $licenceDocument;
            $data['badge_file_url'] = $badgeDocument['file_url'] ?? null;
            $data['badge_document'] = $badgeDocument;
            $data['medical_certificate_file_url'] = $medicalCertificateDocument['file_url'] ?? null;
            $data['medical_certificate_document'] = $medicalCertificateDocument;
        }

        return $data;
    }

    private function depotPayload($depot): ?array
    {
        if (! $depot) {
            return null;
        }

        return [
            'id' => $depot->id,
            'code' => $depot->code,
            'name' => $depot->name,
            'short_name' => $depot->short_name,
            'is_active' => $depot->is_active,
        ];
    }

    private function documentPayload(?string $type, array $keywords): ?array
    {
        $document = $this->documentsFor($type)
            ->first(function ($document) use ($keywords): bool {
                $name = Str::lower($document->documentType?->name ?? '');
                $code = Str::lower($document->documentType?->code ?? '');

                return Str::contains($name, $keywords)
                    || Str::contains($code, $keywords);
            });

        if (! $document) {
            return null;
        }

        return [
            'id' => $document->id,
            'document_type_id' => $document->hrms_document_type_id,
            'document_type_name' => $document->documentType?->name,
            'original_name' => $document->original_name,
            'file_url' => $document->file_path ? asset(Storage::url($document->file_path)) : null,
            'is_verified' => $document->is_verified,
            'expiry_date' => $this->formatDate($document->expiry_date),
        ];
    }

    private function documentExpiryStatus(?string $expiryDate): string
    {
        if (! $expiryDate) {
            return 'active';
        }

        $expiry = Carbon::createFromFormat('d M Y', $expiryDate)->startOfDay();
        $today = Carbon::today();

        if ($expiry->lt($today)) {
            return 'expired';
        }

        if ($expiry->lt($today->copy()->addMonths(6))) {
            return 'expire_soon';
        }

        return 'active';
    }

    private function documentsFor(?string $type)
    {
        return match ($type) {
            'driver' => $this->driverDocuments,
            'controller' => $this->controllerDocuments,
            'supervisor' => $this->supervisorDocuments,
            default => collect(),
        };
    }

    private function readableValue(string $value): string
    {
        return Str::of($value)
            ->replace(['_', '-'], ' ')
            ->title()
            ->toString();
    }

    private function formatDate($value): ?string
    {
        if (! $value) {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->format('d M Y');
        }

        return Carbon::parse($value)->format('d M Y');
    }
}
