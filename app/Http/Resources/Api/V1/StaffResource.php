<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profile = $this->staffProfile;

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
                'reporting_to' => $profile?->reportingTo ? ['id' => $profile->reportingTo->id, 'name' => $profile->reportingTo->name] : null,
                'depot' => $profile?->depot ? ['id' => $profile->depot->id, 'name' => $profile->depot->name] : null,
                'category' => $profile?->category,
                'employment_type' => $profile?->employment_type,
                'date_of_joining' => $profile?->date_of_joining?->format('d M Y'),
                'date_of_birth' => $profile?->date_of_birth?->format('d M Y'),
                'father_name' => $profile?->father_name,
                'country' => $profile?->country,
                'state' => $profile?->state?->name,
                'district' => $profile?->district?->name,
                'location' => $profile?->location?->name,
            ],
        ];
    }
}
