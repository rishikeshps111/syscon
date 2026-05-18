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
        Schema::create('driver_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('alternate_country_code')->nullable();
            $table->string('alternate_phone')->nullable();
            $table->string('aadhaar_number')->unique();
            $table->string('country')->default('India');
            $table->foreignId('state_id')->constrained('states')->cascadeOnDelete();
            $table->foreignId('district_id')->constrained('districts')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->string('pincode', 10);
            $table->text('address');
            $table->string('license_number')->unique();
            $table->enum('license_type', ['lmv', 'hmv', 'transport']);
            $table->date('issue_date');
            $table->date('expiry_date');
            $table->string('badge_number')->nullable();
            $table->date('badge_expiry_date')->nullable();
            $table->enum('employment_type', ['permanent', 'contract']);
            $table->date('joining_date');
            $table->decimal('salary', 12, 2);
            $table->foreignId('depot_id')->constrained('depots')->cascadeOnDelete();
            $table->foreignId('branch_location_id')->constrained('branch_locations')->cascadeOnDelete();
            $table->string('account_number');
            $table->string('ifsc_code', 20);
            $table->string('emergency_contact_name');
            $table->string('emergency_contact_no');
            $table->date('medical_fitness_expiry');
            $table->enum('police_verification_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_profiles');
    }
};
