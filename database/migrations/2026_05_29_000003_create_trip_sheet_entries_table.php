<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tripTable = Schema::hasTable('trips') ? 'trips' : 'trip_setups';

        Schema::create('trip_sheet_entries', function (Blueprint $table) use ($tripTable) {
            $table->id();
            $table->foreignId('trip_id')->constrained($tripTable)->cascadeOnDelete();
            $table->date('trip_date');
            $table->time('departure_time')->nullable();
            $table->time('arrival_time')->nullable();
            $table->time('actual_start_time')->nullable();
            $table->time('actual_reach_time')->nullable();
            $table->string('verified_by')->nullable();
            $table->string('approved_by')->nullable();
            $table->string('shift', 20)->nullable();
            $table->foreignId('driver_profile_id')->nullable()->constrained('driver_profiles')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['trip_id', 'trip_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_sheet_entries');
    }
};
