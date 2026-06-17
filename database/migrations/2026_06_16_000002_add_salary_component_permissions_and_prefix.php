<?php

use App\Models\Prefix;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['view', 'create', 'edit', 'delete'] as $action) {
            Permission::firstOrCreate(
                ['name' => 'salary-components.' . $action, 'guard_name' => 'web'],
                ['group_name' => 'Salary Components']
            );
        }

        Prefix::updateOrCreate(
            ['module' => 'Salary Component Module'],
            ['prefix' => 'SC', 'is_active' => true]
        );

        Role::where('name', 'Super Admin')
            ->where('guard_name', 'web')
            ->first()
            ?->givePermissionTo(Permission::whereIn('name', [
                'salary-components.view',
                'salary-components.create',
                'salary-components.edit',
                'salary-components.delete',
            ])->get());
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::whereIn('name', [
            'salary-components.view',
            'salary-components.create',
            'salary-components.edit',
            'salary-components.delete',
        ])->delete();

        Prefix::where('module', 'Salary Component Module')->delete();
    }
};
