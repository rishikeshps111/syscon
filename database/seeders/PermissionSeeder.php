<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Example permissions (you can adjust later)
        $permissions = [
            'Prefix' => [
                'prefixes.create',
                'prefixes.edit',
                'prefixes.delete',
                'prefixes.view',
            ],
            'State' => [
                'states.create',
                'states.edit',
                'states.delete',
                'states.view',
            ],
            'District' => [
                'districts.create',
                'districts.edit',
                'districts.delete',
                'districts.view',
            ],
            'Location' => [
                'locations.create',
                'locations.edit',
                'locations.delete',
                'locations.view',
            ],
            'Service Type' => [
                'service-types.create',
                'service-types.edit',
                'service-types.delete',
                'service-types.view',
            ],
            'Vehicle Classification' => [
                'vehicle-classifications.create',
                'vehicle-classifications.edit',
                'vehicle-classifications.delete',
                'vehicle-classifications.view',
            ],
            'Document Type' => [
                'document-types.create',
                'document-types.edit',
                'document-types.delete',
                'document-types.view',
            ],
            'Complaint Category' => [
                'complaint-categories.create',
                'complaint-categories.edit',
                'complaint-categories.delete',
                'complaint-categories.view',
            ],
            'Depot' => [
                'depots.create',
                'depots.edit',
                'depots.delete',
                'depots.view',
            ],
            'Route' => [
                'routes.create',
                'routes.edit',
                'routes.delete',
                'routes.view',
            ],
            'Vehicle Management' => [
                'vehicles.create',
                'vehicles.edit',
                'vehicles.delete',
                'vehicles.view',
            ],
            'Trip Setup' => [
                'trip-setups.create',
                'trip-setups.edit',
                'trip-setups.delete',
                'trip-setups.view',
            ],
            'OEM Type' => [
                'oem-types.create',
                'oem-types.edit',
                'oem-types.delete',
                'oem-types.view',
            ],
            'OEM' => [
                'oems.create',
                'oems.edit',
                'oems.delete',
                'oems.view',
            ],
            'Branch Location' => [
                'branch-locations.create',
                'branch-locations.edit',
                'branch-locations.delete',
                'branch-locations.view',
            ],
            'Department' => [
                'departments.create',
                'departments.edit',
                'departments.delete',
                'departments.view',
            ],
            'Level' => [
                'levels.create',
                'levels.edit',
                'levels.delete',
                'levels.view',
            ],
            'Designation' => [
                'designations.create',
                'designations.edit',
                'designations.delete',
                'designations.view',
            ],
            'HRMS Document Type' => [
                'hrms-document-types.create',
                'hrms-document-types.edit',
                'hrms-document-types.delete',
                'hrms-document-types.view',
            ],
            'Leave Type' => [
                'leave-types.create',
                'leave-types.edit',
                'leave-types.delete',
                'leave-types.view',
            ],
            'Shift Setting' => [
                'shift-settings.create',
                'shift-settings.edit',
                'shift-settings.delete',
                'shift-settings.view',
            ],
            'Leave Management' => [
                'leaves.create',
                'leaves.edit',
                'leaves.delete',
                'leaves.view',
            ],
            'Holiday' => [
                'holidays.create',
                'holidays.edit',
                'holidays.delete',
                'holidays.view',
            ],
            'Staff Management' => [
                'staff-management.create',
                'staff-management.edit',
                'staff-management.delete',
                'staff-management.view',
            ],
            'Controller Management' => [
                'controller-management.create',
                'controller-management.edit',
                'controller-management.delete',
                'controller-management.view',
            ],
            'Supervisor Management' => [
                'supervisor-management.create',
                'supervisor-management.edit',
                'supervisor-management.delete',
                'supervisor-management.view',
            ],
            'Driver Management' => [
                'driver-management.create',
                'driver-management.edit',
                'driver-management.delete',
                'driver-management.view',
            ],
            'Complaints' => [
                'complaints.create',
                'complaints.edit',
                'complaints.delete',
                'complaints.view',
            ],
        ];

        foreach ($permissions as $group => $perms) {
            foreach ($perms as $perm) {
                Permission::firstOrCreate(
                    ['name' => $perm, 'guard_name' => 'web'],
                    ['group_name' => $group]
                );
            }
        }

        Role::where('name', 'Super Admin')
            ->where('guard_name', 'web')
            ->first()
            ?->syncPermissions(Permission::all());
    }
}
