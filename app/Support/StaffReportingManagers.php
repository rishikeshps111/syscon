<?php

namespace App\Support;

use App\Models\Designation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class StaffReportingManagers
{
    public static function query(int $designationId, int $depotId, ?int $excludeUserId = null): Builder
    {
        $reportingRole = Designation::query()
            ->with('reportingRole:id,name')
            ->find($designationId)
            ?->reportingRole;

        $query = User::query()->whereRaw('1 = 0');

        if (! $reportingRole) {
            return $query;
        }

        $profileRelation = match ($reportingRole->name) {
            'Supervisor' => 'supervisorProfile',
            'Controller' => 'controllerProfile',
            'Driver' => 'driverProfile',
            default => 'staffProfile',
        };

        return User::query()
            ->role($reportingRole->name)
            ->where('users.is_active', true)
            ->whereHas($profileRelation, fn (Builder $profileQuery) => $profileQuery->where('depot_id', $depotId))
            ->when($excludeUserId, fn (Builder $userQuery) => $userQuery->where('users.id', '<>', $excludeUserId));
    }
}
