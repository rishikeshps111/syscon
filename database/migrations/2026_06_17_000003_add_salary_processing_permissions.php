<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $permissions = [
        'salary-processing.view',
        'salary-processing.create',
        'salary-processing.edit',
        'salary-processing.delete',
        'salary-processing.approve',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['group_name' => 'Salary Processing']
            );
        }

        Role::where('name', 'Super Admin')
            ->where('guard_name', 'web')
            ->first()
            ?->givePermissionTo(Permission::whereIn('name', $this->permissions)->get());

        Role::where('name', 'Finance Officer')
            ->where('guard_name', 'web')
            ->first()
            ?->givePermissionTo(Permission::whereIn('name', [
                'salary-processing.view',
                'salary-processing.approve',
            ])->get());
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionIds = Permission::whereIn('name', $this->permissions)->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        Permission::whereIn('id', $permissionIds)->delete();
    }
};
