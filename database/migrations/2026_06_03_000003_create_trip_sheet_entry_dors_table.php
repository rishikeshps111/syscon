<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_sheet_entry_dors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_sheet_entry_id')->unique()->constrained('trip_sheet_entries')->cascadeOnDelete();
            $table->string('depot_name')->nullable();
            $table->date('dor_date')->nullable();
            $table->string('bus_no')->nullable();
            $table->string('route_no')->nullable();
            $table->string('duty')->nullable();
            $table->string('shift')->nullable();
            $table->string('driver_badge_no')->nullable();
            $table->time('schedule_start_time')->nullable();
            $table->time('schedule_end_time')->nullable();
            $table->time('actual_start_time')->nullable();
            $table->time('actual_end_time')->nullable();
            $table->string('start_punc')->nullable();
            $table->time('route_completion_time')->nullable();
            $table->decimal('schedule_km', 10, 2)->nullable();
            $table->decimal('route_km_loss', 10, 2)->nullable();
            $table->decimal('actual_route_km', 10, 2)->nullable();
            $table->unsignedInteger('schedule_trip')->nullable();
            $table->unsignedInteger('actual_trip')->nullable();
            $table->unsignedInteger('miss_trip')->nullable();
            $table->decimal('odometer_start_reading', 10, 2)->nullable();
            $table->decimal('odometer_end_reading', 10, 2)->nullable();
            $table->decimal('odometer_diff_km', 10, 2)->nullable();
            $table->decimal('difference', 10, 2)->nullable();
            $table->string('account_responsible')->nullable();
            $table->text('reason_for_kilometer_loss')->nullable();
            $table->text('after_sales_reason')->nullable();
            $table->text('penalty_infraction')->nullable();
            $table->text('remarks')->nullable();
            $table->decimal('route_start_soc_percent', 5, 2)->nullable();
            $table->decimal('route_end_soc_percent', 5, 2)->nullable();
            $table->decimal('soc_consumption_on_route_percent', 5, 2)->nullable();
            $table->decimal('soc_per_km', 10, 4)->nullable();
            $table->decimal('run_kilometer_per_soc', 10, 4)->nullable();
            $table->decimal('dor_kwh_per_km_odo', 10, 4)->nullable();
            $table->decimal('dor_kwh_per_km_act', 10, 4)->nullable();
            $table->decimal('dcr_kwh', 10, 2)->nullable();
            $table->decimal('dcr_charged_soc', 5, 2)->nullable();
            $table->decimal('energy_absorption', 10, 4)->nullable();
            $table->decimal('battery_size_kwh', 10, 2)->nullable();
            $table->decimal('vp1', 10, 4)->nullable();
            $table->decimal('vp2', 10, 4)->nullable();
            $table->decimal('dp', 10, 4)->nullable();
            $table->decimal('penalty', 10, 2)->nullable();
            $table->string('model_9m_12m')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_sheet_entry_dors');
    }
};
