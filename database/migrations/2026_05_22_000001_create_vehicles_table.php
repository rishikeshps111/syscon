<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained('states')->cascadeOnDelete();
            $table->foreignId('oem_id')->constrained('oems')->cascadeOnDelete();
            $table->foreignId('depot_id')->constrained('depots')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branch_locations')->cascadeOnDelete();
            $table->string('vehicle_code', 50)->nullable()->unique();
            $table->string('vehicle_no', 20)->unique();
            $table->enum('vehicle_type', ['BUS', 'CAR', 'VAN', 'TRUCK', 'AUTO']);
            $table->enum('fuel_type', ['ELECTRIC', 'DIESEL', 'PETROL', 'CNG', 'HYBRID']);
            $table->enum('vehicle_category', ['Passenger', 'Cargo']);
            $table->string('make')->nullable();
            $table->string('model')->nullable();
            $table->string('variant')->nullable();
            $table->unsignedInteger('capacity_seating')->nullable();
            $table->decimal('capacity_load', 10, 2)->nullable();
            $table->decimal('battery_capacity', 10, 2)->nullable();
            $table->unsignedInteger('range_km')->nullable();
            $table->string('engine_no')->nullable();
            $table->string('chassis_no')->unique();
            $table->date('registration_date')->nullable();
            $table->date('registration_valid_upto')->nullable();
            $table->date('fitness_expiry')->nullable();
            $table->date('permit_expiry')->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->date('pollution_expiry')->nullable();
            $table->boolean('gps_enabled')->default(false);
            $table->string('gps_imei')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Under Maintenance', 'Scrap'])->default('Active');
            $table->boolean('is_verified')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->text('remarks')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['state_id', 'oem_id']);
            $table->index(['vehicle_type', 'fuel_type', 'status']);
            $table->index('gps_enabled');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
