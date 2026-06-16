<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $permission = 'license-expiry-reports.view';

    public function up(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        DB::table('permissions')->updateOrInsert(
            ['name' => $this->permission, 'guard_name' => 'web'],
            ['group_name' => 'Reports', 'created_at' => now(), 'updated_at' => now()]
        );

        $superAdmin = DB::table('roles')
            ->where('name', 'Super Admin')
            ->where('guard_name', 'web')
            ->first();
        $permission = DB::table('permissions')
            ->where('name', $this->permission)
            ->where('guard_name', 'web')
            ->first();

        if ($superAdmin && $permission) {
            DB::table('role_has_permissions')->updateOrInsert([
                'role_id' => $superAdmin->id,
                'permission_id' => $permission->id,
            ]);
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionIds = DB::table('permissions')
            ->where('name', $this->permission)
            ->where('guard_name', 'web')
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
