<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('housekeeping_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('alternate_country_code')->nullable();
            $table->string('alternate_phone')->nullable();
            $table->string('aadhaar_number')->unique();
            $table->string('country')->default('India');
            $table->foreignId('state_id')->constrained()->restrictOnDelete();
            $table->foreignId('district_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->string('pincode', 10);
            $table->text('address');
            $table->enum('employment_type', ['permanent', 'contract']);
            $table->date('joining_date');
            $table->decimal('salary', 12, 2)->default(0);
            $table->foreignId('depot_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_location_id')->constrained()->restrictOnDelete();
            $table->string('account_number');
            $table->string('ifsc_code', 20);
            $table->string('emergency_contact_name');
            $table->string('emergency_country_code')->default('+91');
            $table->string('emergency_contact_no');
            $table->date('medical_fitness_expiry');
            $table->enum('police_verification_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('housekeeping_profiles');
    }
};
