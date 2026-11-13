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
        foreach (['staff-management' => 'Staff Management', 'driver-management' => 'Driver Management'] as $module => $group) {
            $permission = Permission::firstOrCreate(['name' => $module.'.view-salary', 'guard_name' => 'web'], ['group_name' => $group]);
            Role::where('name', 'Super Admin')->where('guard_name', 'web')->get()
                ->each(fn (Role $role) => $role->givePermissionTo($permission));
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $ids = Permission::whereIn('name', ['staff-management.view-salary', 'driver-management.view-salary'])->where('guard_name', 'web')->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        Permission::whereIn('id', $ids)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
