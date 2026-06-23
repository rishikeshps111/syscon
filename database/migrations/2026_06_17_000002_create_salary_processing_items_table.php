<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_processing_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salary_processing_id')->constrained('salary_processings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('aadhaar_no')->nullable();
            $table->decimal('total_leave_taken', 8, 2)->default(0);
            $table->unsignedInteger('total_shifts_completed')->default(0);
            $table->unsignedTinyInteger('total_working_days')->default(0);
            $table->decimal('lop', 8, 2)->default(0);
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('deduction', 12, 2)->default(0);
            $table->decimal('incentive', 12, 2)->default(0);
            $table->decimal('unauthorized_leaves', 8, 2)->default(0);
            $table->decimal('net_salary', 12, 2)->default(0);
            $table->json('salary_split')->nullable();
            $table->timestamps();

            $table->unique(['salary_processing_id', 'user_id'], 'salary_processing_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_processing_items');
    }
};
