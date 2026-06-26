<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate(
            ['name' => 'salary-slips.view', 'guard_name' => 'web'],
            ['group_name' => 'Generate Pay Slip']
        );

        Role::where('name', 'Super Admin')
            ->where('guard_name', 'web')
            ->first()
            ?->givePermissionTo($permission);
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionIds = Permission::where('name', 'salary-slips.view')->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        Permission::whereIn('id', $permissionIds)->delete();
    }
};
