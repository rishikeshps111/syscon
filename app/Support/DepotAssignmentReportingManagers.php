<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class DepotAssignmentReportingManagers
{
    public static function query(string $module, int $depotId): Builder
    {
        if (! in_array($module, ['driver', 'controller'], true)) {
            return User::query()->whereRaw('1 = 0');
        }

        return User::query()
            ->where('users.is_active', true)
            ->where(function (Builder $query) use ($module, $depotId) {
                $query->where(function (Builder $supervisors) use ($depotId) {
                    $supervisors->role('Supervisor')
                        ->whereHas('supervisorProfile', fn (Builder $profile) => $profile->where('depot_id', $depotId));
                });

                if ($module === 'driver') {
                    $query->orWhere(function (Builder $controllers) use ($depotId) {
                        $controllers->role('Controller')
                            ->whereHas('controllerProfile', fn (Builder $profile) => $profile->where('depot_id', $depotId));
                    });
                }
            });
    }
}
