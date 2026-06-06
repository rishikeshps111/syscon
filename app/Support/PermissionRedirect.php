<?php

namespace App\Support;

use App\Models\User;

class PermissionRedirect
{
    public static function routeNameFor(User $user): string
    {
        if ($user->hasRole('Super Admin')) {
            return 'dashboard';
        }

        foreach (self::routes() as $permission => $route) {
            if ($user->can($permission)) {
                return $route;
            }
        }

        return 'dashboard';
    }

    public static function routes(): array
    {
        return [
            'prefixes.view' => 'prefixes.index',
            'states.view' => 'states.index',
            'districts.view' => 'districts.index',
            'locations.view' => 'locations.index',
            'service-types.view' => 'service-types.index',
            'oem-types.view' => 'oem-types.index',
            'depots.view' => 'depots.index',
            'vehicle-classifications.view' => 'vehicle-classifications.index',
            'document-types.view' => 'document-types.index',
            'complaint-categories.view' => 'complaint-categories.index',
            'oems.view' => 'oems.index',
            'branch-locations.view' => 'branch-locations.index',
            'departments.view' => 'departments.index',
            'levels.view' => 'levels.index',
            'designations.view' => 'designations.index',
            'role-permissions.view' => 'role-permissions.index',
            'hrms-document-types.view' => 'hrms-document-types.index',
            'leave-types.view' => 'leave-types.index',
            'shift-settings.view' => 'shift-settings.index',
            'holidays.view' => 'holidays.index',
            'staff-management.view' => 'staff-management.index',
            'driver-management.view' => 'driver-management.index',
            'controller-management.view' => 'controller-management.index',
            'supervisor-management.view' => 'supervisor-management.index',
            'leaves.view' => 'leaves.index',
            'attendance-management.view' => 'attendance-management.index',
            'routes.view' => 'routes.index',
            'vehicles.view' => 'vehicles.index',
            'trips.view' => 'trips.index',
            'rosters.view' => 'rosters.index',
            'complaints.view' => 'complaints.index',
            'settings.view' => 'financial-year-settings.index',
            'user-logs.view' => 'user-logs.index',
        ];
    }
}
