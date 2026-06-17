<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('designation_id')->nullable()->constrained('designations')->nullOnDelete();
            $table->string('code')->nullable()->unique();
            $table->string('component_name');
            $table->enum('type', ['earning', 'deduction']);
            $table->boolean('is_applicable')->default(true);
            $table->enum('calculation_type', ['fixed', 'percentage', 'per_shift', 'per_trip', 'formula']);
            $table->decimal('default_value', 12, 2)->default(0);
            $table->boolean('is_editable_in_payroll')->default(true);
            $table->boolean('is_mandatory')->default(false);
            $table->timestamps();

            $table->unique(['role_id', 'designation_id', 'component_name'], 'salary_components_role_designation_name_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_components');
    }
};
