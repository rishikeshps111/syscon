<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->enum('maintenance_type', ['Service', 'Repair', 'Breakdown']);
            $table->text('description')->nullable();
            $table->decimal('cost', 12, 2)->nullable();
            $table->string('vendor_name')->nullable();
            $table->date('service_date');
            $table->date('next_service_due')->nullable();
            $table->enum('status', ['Open', 'Closed'])->default('Open');
            $table->timestamps();
            $table->index(['vehicle_id', 'status']);
            $table->index('service_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_maintenance_logs');
    }
};
