<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_fuel_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->enum('fuel_type', ['ELECTRIC', 'DIESEL', 'PETROL', 'CNG', 'HYBRID']);
            $table->decimal('quantity', 12, 2);
            $table->decimal('cost', 12, 2)->nullable();
            $table->unsignedInteger('odometer_reading')->nullable();
            $table->date('date');
            $table->timestamps();
            $table->index(['vehicle_id', 'date']);
            $table->index('fuel_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_fuel_logs');
    }
};
