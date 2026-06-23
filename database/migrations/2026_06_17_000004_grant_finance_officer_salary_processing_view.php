<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

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

        $financeOfficer = Role::where('name', 'Finance Officer')->where('guard_name', 'web')->first();

        if ($financeOfficer) {
            $financeOfficer->revokePermissionTo('salary-processing.view');
        }
    }
};
