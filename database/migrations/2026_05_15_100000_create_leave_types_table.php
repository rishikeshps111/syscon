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
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable()->unique();
            $table->string('leave_name');
            $table->string('short_name', 20);
            $table->enum('leave_category', ['Paid Leave', 'Unpaid Leave']);
            $table->decimal('max_leaves_per_year', 8, 2)->nullable();
            $table->boolean('carry_forward_allowed')->default(false);
            $table->decimal('max_carry_forward_limit', 8, 2)->nullable();
            $table->boolean('encashment_allowed')->default(false);
            $table->enum('applicable_for', ['all_employees', 'drivers', 'controllers', 'supervisors'])->default('all_employees');
            $table->enum('gender_specific', ['all', 'male', 'female'])->default('all');
            $table->string('minimum_service_required')->nullable();
            $table->decimal('minimum_leave_days', 8, 2)->default(1);
            $table->decimal('maximum_leave_days_per_request', 8, 2)->nullable();
            $table->unsignedInteger('advance_notice_days')->nullable();
            $table->boolean('allow_half_day')->default(false);
            $table->boolean('requires_approval')->default(true);
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
        Schema::dropIfExists('leave_types');
    }
};
