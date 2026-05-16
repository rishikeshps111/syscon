<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable()->unique();
            $table->string('holiday_name');
            $table->date('holiday_date');
            $table->enum('holiday_type', ['national', 'state', 'company']);
            $table->enum('applicable_location', ['all', 'state', 'branch'])->default('all');
            $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();
            $table->foreignId('branch_location_id')->nullable()->constrained('branch_locations')->nullOnDelete();
            $table->enum('applicable_for', ['all_employees', 'specific_departments', 'specific_designations'])->default('all_employees');
            $table->json('department_ids')->nullable();
            $table->json('designation_ids')->nullable();
            $table->enum('holiday_duration', ['full_day', 'half_day'])->default('full_day');
            $table->boolean('is_recurring_yearly')->default(false);
            $table->boolean('is_active')->default(true);
            $table->longText('description')->nullable();
            $table->longText('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
