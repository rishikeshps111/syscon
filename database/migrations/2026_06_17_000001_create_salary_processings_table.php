<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_processings', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->foreignId('depot_id')->constrained('depots')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->date('salary_date')->nullable();
            $table->enum('payment_method', ['Cheque', 'Treasury', 'Bank Transfer'])->nullable();
            $table->text('remarks')->nullable();
            $table->enum('status', ['Draft', 'Completed', 'Approved'])->default('Completed');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['year', 'month', 'depot_id', 'role_id'], 'salary_processing_period_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_processings');
    }
};
