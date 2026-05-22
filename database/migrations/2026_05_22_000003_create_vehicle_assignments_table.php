<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('route_id')->constrained('routes')->cascadeOnDelete();
            $table->dateTime('assigned_from');
            $table->dateTime('assigned_to')->nullable();
            $table->enum('status', ['Active', 'Completed'])->default('Active');
            $table->timestamps();
            $table->index(['vehicle_id', 'status']);
            $table->index(['driver_id', 'route_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_assignments');
    }
};
