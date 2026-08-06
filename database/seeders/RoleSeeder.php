<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    use WithoutModelEvents;
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'Super Admin', 'guard_name' => 'web'],
            ['name' => 'Staff', 'guard_name' => 'web'],
            ['name' => 'Driver', 'guard_name' => 'web'],
            ['name' => 'Controller', 'guard_name' => 'web'],
            ['name' => 'Supervisor', 'guard_name' => 'web'],
            ['name' => 'Housekeeping', 'guard_name' => 'web']
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name'], 'guard_name' => $role['guard_name']]
            );
        }

        // Assign all permissions to Super Admin role
        $superAdminRole = Role::where('name', 'Super Admin')->first();
        if ($superAdminRole) {
            $allPermissions = Permission::all();
            $superAdminRole->syncPermissions($allPermissions);
        }
    }
}
