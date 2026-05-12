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
