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
        Schema::create('hrms_document_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable()->unique();
            $table->string('name');
            $table->enum('category', ['Identity Proof', 'Address Proof', 'Financial', 'Educational', 'Legal'])->nullable();
            $table->enum('applicable_for', ['all', 'driver', 'controller', 'supervisor'])->nullable();
            $table->enum('allowed_file_types', ['pdf', 'jpg', 'png', 'doc'])->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_mandatory')->default(false);
            $table->boolean('is_expiry_required')->default(false);
            $table->longText('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hrms_document_types');
    }
};
