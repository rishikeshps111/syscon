<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $permissions = [
        'dor-account-responsibles.view',
        'dor-account-responsibles.create',
        'dor-account-responsibles.edit',
        'dor-account-responsibles.delete',
        'dor-kilometer-loss-reasons.view',
        'dor-kilometer-loss-reasons.create',
        'dor-kilometer-loss-reasons.edit',
        'dor-kilometer-loss-reasons.delete',
    ];

    public function up(): void
    {
        $now = now();

        foreach ($this->permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission, 'guard_name' => 'web'],
                ['group_name' => 'Masters', 'updated_at' => $now, 'created_at' => $now]
            );
        }

        $superAdmin = DB::table('roles')->where('name', 'Super Admin')->first();

        if (! $superAdmin) {
            return;
        }

        $permissionIds = DB::table('permissions')->whereIn('name', $this->permissions)->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('role_has_permissions')->updateOrInsert([
                'permission_id' => $permissionId,
                'role_id' => $superAdmin->id,
            ]);
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')->whereIn('name', $this->permissions)->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
