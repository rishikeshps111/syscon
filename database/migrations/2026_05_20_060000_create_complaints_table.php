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
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable()->unique();
            $table->date('complaint_date');
            $table->enum('reported_by_role', ['controller', 'supervisor']);
            $table->foreignId('reported_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('against_role', ['driver', 'controller']);
            $table->foreignId('against_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('complaint_category_id')->constrained('complaint_categories')->restrictOnDelete();
            $table->text('description');
            $table->json('attachment_paths')->nullable();
            $table->enum('severity', ['low', 'medium', 'high'])->default('low');
            $table->enum('status', ['pending', 'in_review', 'action_taken', 'closed', 'rejected'])->default('pending');
            $table->enum('assigned_to', ['admin', 'hr', 'manager'])->nullable();
            $table->enum('action_taken', ['warning', 'suspension', 'fine'])->nullable();
            $table->date('action_date')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
