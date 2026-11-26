<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverManagementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profile = $this->driverProfile;

        return [
            'id' => $this->id,
            'code' => $this->code,
            'ref_code' => $this->ref_code,
            'name' => $this->name,
            'email' => $this->email,
            'country_code' => $this->country_code,
            'phone' => $this->phone,
            'full_phone' => $this->full_phone,
            'avatar_url' => $this->avatar_url,
            'is_active' => $this->is_active,
            'profile' => [
                'designation' => $profile?->designation ? ['id' => $profile->designation->id, 'name' => $profile->designation->name] : null,
                'depot' => $profile?->depot ? ['id' => $profile->depot->id, 'name' => $profile->depot->name] : null,
                'branch' => $profile?->branchLocation ? ['id' => $profile->branchLocation->id, 'name' => $profile->branchLocation->name] : null,
                'employment_type' => $profile?->employment_type,
                'joining_date' => $profile?->joining_date?->format('d M Y'),
                'alternate_country_code' => $profile?->alternate_country_code,
                'alternate_phone' => $profile?->alternate_phone,
                'country' => $profile?->country,
                'state' => $profile?->state?->name,
                'district' => $profile?->district?->name,
                'location' => $profile?->location?->name,
                'license_type' => $profile?->license_type,
                'license_number' => $profile?->license_number,
                'license_issue_date' => $profile?->issue_date?->format('d M Y'),
                'license_expiry_date' => $profile?->expiry_date?->format('d M Y'),
                'badge_number' => $profile?->badge_number,
                'badge_expiry_date' => $profile?->badge_expiry_date?->format('d M Y'),
                'medical_fitness_expiry' => $profile?->medical_fitness_expiry?->format('d M Y'),
                'police_verification_status' => $profile?->police_verification_status,
                'verification_status' => $profile?->verification_status,
            ],
        ];
    }
}
