<?php

namespace App\Support;

use App\Models\HrmsDocumentType;
use App\Models\User;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class EmployeeActivationGuard
{
    private const DOCUMENT_RELATIONS = [
        'Staff' => 'staffDocuments',
        'Driver' => 'driverDocuments',
        'Controller' => 'controllerDocuments',
        'Supervisor' => 'supervisorDocuments',
        'Housekeeping' => 'housekeepingDocuments',
    ];

    /** @return Collection<int, HrmsDocumentType> */
    public function missingMandatoryDocuments(User $user, string $role): Collection
    {
        $relation = self::DOCUMENT_RELATIONS[$role] ?? null;

        if (! $relation) {
            throw new InvalidArgumentException("Unsupported employee role [{$role}].");
        }

        $required = HrmsDocumentType::query()
            ->where('is_active', true)
            ->where('is_mandatory', true)
            ->whereIn('applicable_for', ['all', strtolower($role)])
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($required->isEmpty()) {
            return collect();
        }

        $uploadedTypeIds = $user->{$relation}()
            ->whereIn('hrms_document_type_id', $required->pluck('id'))
            ->pluck('hrms_document_type_id')
            ->map(fn ($id) => (int) $id)
            ->unique();

        return $required
            ->reject(fn (HrmsDocumentType $type) => $uploadedTypeIds->contains($type->id))
            ->values();
    }
}
