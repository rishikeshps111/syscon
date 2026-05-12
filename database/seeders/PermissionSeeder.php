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
            'Trip Setup' => [
                'trip-setups.create',
                'trip-setups.edit',
                'trip-setups.delete',
                'trip-setups.view',
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
