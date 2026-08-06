<?php

namespace App\Support;

use App\Models\Depot;

class UserCodeGenerator
{
    private const ROLE_CODES = [
        'Driver' => 'DRV',
        'Supervisor' => 'SPV',
        'Controller' => 'CON',
        'Staff' => 'MNGT',
    ];

    public static function generate(string $role, int $depotId, int $sequence): string
    {
        $roleCode = self::ROLE_CODES[$role] ?? null;

        if ($roleCode === null) {
            throw new \InvalidArgumentException("Unsupported user role [{$role}].");
        }

        $depotShortName = Depot::query()->findOrFail($depotId)->short_name;

        return sprintf('SFN/%s/%s-%04d', mb_strtoupper(trim($depotShortName)), $roleCode, $sequence);
    }
}
