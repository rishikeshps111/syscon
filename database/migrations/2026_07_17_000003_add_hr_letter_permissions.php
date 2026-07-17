<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private array $permissions = [
        'hr-letter-templates.view', 'hr-letter-templates.create', 'hr-letter-templates.edit', 'hr-letter-templates.delete',
        'hr-letters.view', 'hr-letters.generate',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach ($this->permissions as $name) Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web'], ['group_name' => str_starts_with($name, 'hr-letter-templates') ? 'HR Letter Templates' : 'HR Letters']);
        Role::whereIn('name', ['Super Admin', 'HR'])->get()->each(fn ($role) => $role->givePermissionTo($this->permissions));
    }

    public function down(): void
    {
        Permission::whereIn('name', $this->permissions)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
