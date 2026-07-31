<?php

use App\Models\Prefix;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable()->unique();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('designation_id')->nullable()->constrained('designations')->cascadeOnDelete();
            $table->timestamps();
            $table->index(['role_id', 'designation_id']);
        });

        Schema::create('salary_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salary_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salary_component_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();
            $table->unique(['salary_template_id', 'salary_component_id'], 'salary_template_component_unique');
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['view', 'create', 'edit', 'delete'] as $action) {
            Permission::firstOrCreate(
                ['name' => 'salary-templates.' . $action, 'guard_name' => 'web'],
                ['group_name' => 'Salary Templates']
            );
        }

        Prefix::updateOrCreate(
            ['module' => 'Salary Template Module'],
            ['prefix' => 'ST', 'is_active' => true]
        );

        Role::where('name', 'Super Admin')->where('guard_name', 'web')->first()
            ?->givePermissionTo(Permission::whereIn('name', [
                'salary-templates.view',
                'salary-templates.create',
                'salary-templates.edit',
                'salary-templates.delete',
            ])->get());
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::where('name', 'like', 'salary-templates.%')->delete();
        Prefix::where('module', 'Salary Template Module')->delete();
        Schema::dropIfExists('salary_template_items');
        Schema::dropIfExists('salary_templates');
    }
};
