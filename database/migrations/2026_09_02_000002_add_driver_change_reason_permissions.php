<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $permissions = [
        'driver-change-reasons.view',
        'driver-change-reasons.create',
        'driver-change-reasons.edit',
        'driver-change-reasons.delete',
        'driver-change-reasons.export',
        'driver-change-reasons.status',
    ];

    public function up(): void
    {
        $now = now();
        foreach ($this->permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission, 'guard_name' => 'web'],
                ['group_name' => 'Trip Management', 'created_at' => $now, 'updated_at' => $now]
            );
        }

        $roleId = DB::table('roles')->where('name', 'Super Admin')->where('guard_name', 'web')->value('id');
        if ($roleId) {
            foreach (DB::table('permissions')->whereIn('name', $this->permissions)->pluck('id') as $permissionId) {
                DB::table('role_has_permissions')->updateOrInsert(['permission_id' => $permissionId, 'role_id' => $roleId]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('name', $this->permissions)->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
