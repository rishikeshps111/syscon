<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        DB::table('permissions')->updateOrInsert(
            ['name' => 'activity-logs.view', 'guard_name' => 'web'],
            ['group_name' => 'Activity Logs', 'created_at' => now(), 'updated_at' => now()]
        );

        $superAdmin = DB::table('roles')
            ->where('name', 'Super Admin')
            ->where('guard_name', 'web')
            ->first();

        $permission = DB::table('permissions')
            ->where('name', 'activity-logs.view')
            ->where('guard_name', 'web')
            ->first();

        if (! $superAdmin || ! $permission) {
            return;
        }

        DB::table('role_has_permissions')->updateOrInsert([
            'role_id' => $superAdmin->id,
            'permission_id' => $permission->id,
        ]);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionIds = DB::table('permissions')
            ->where('name', 'activity-logs.view')
            ->where('guard_name', 'web')
            ->pluck('id');

        DB::table('role_has_permissions')
            ->whereIn('permission_id', $permissionIds)
            ->delete();

        DB::table('model_has_permissions')
            ->whereIn('permission_id', $permissionIds)
            ->delete();

        DB::table('permissions')
            ->whereIn('id', $permissionIds)
            ->delete();

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
