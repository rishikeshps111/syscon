<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supervisor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('depot_id')->nullable()->constrained('depots')->nullOnDelete();
            $table->enum('employment_type', ['full_time', 'part_time', 'contract'])->nullable();
            $table->string('father_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('aadhaar_number')->nullable();
            $table->string('pan_number')->nullable();
            $table->date('date_of_joining')->nullable();
            $table->string('uan')->nullable();
            $table->string('esic_wc')->nullable();
            $table->string('country')->nullable();
            $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('bank_account_number')->nullable();
            $table->string('ifsc_code')->nullable();
            $table->decimal('basic', 12, 2)->nullable();
            $table->decimal('vda', 12, 2)->nullable();
            $table->decimal('basic_vda', 12, 2)->nullable();
            $table->decimal('hra', 12, 2)->nullable();
            $table->decimal('special_allowance', 12, 2)->nullable();
            $table->decimal('conveyance_allowance', 12, 2)->nullable();
            $table->decimal('bonus', 12, 2)->nullable();
            $table->decimal('gross_salary', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisor_profiles');
    }
};
