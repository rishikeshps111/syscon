<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('permissions')
            ->where('name', 'user-logs.view')
            ->where('guard_name', 'web')
            ->exists();

        if (! $exists) {
            DB::table('permissions')->insert([
                'name' => 'user-logs.view',
                'guard_name' => 'web',
                'group_name' => 'User Logs',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $superAdmin = DB::table('roles')
            ->where('name', 'Super Admin')
            ->where('guard_name', 'web')
            ->first();

        $permission = DB::table('permissions')
            ->where('name', 'user-logs.view')
            ->where('guard_name', 'web')
            ->first();

        if (! $superAdmin || ! $permission) {
            return;
        }

        $assigned = DB::table('role_has_permissions')
            ->where('role_id', $superAdmin->id)
            ->where('permission_id', $permission->id)
            ->exists();

        if (! $assigned) {
            DB::table('role_has_permissions')->insert([
                'role_id' => $superAdmin->id,
                'permission_id' => $permission->id,
            ]);
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->where('name', 'user-logs.view')
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
    }
};
