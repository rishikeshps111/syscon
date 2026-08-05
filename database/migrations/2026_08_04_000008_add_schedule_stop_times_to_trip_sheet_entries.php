<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_sheet_entry_stop_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_sheet_entry_id')
                ->constrained('trip_sheet_entries')
                ->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('route_stop_id')->nullable()->constrained('route_stops')->nullOnDelete();
            $table->unsignedInteger('sequence_no');
            $table->string('location_name');
            $table->string('event', 20);
            $table->boolean('show_location')->default(true);
            $table->time('scheduled_time');
            $table->timestamps();

            $table->unique(['trip_sheet_entry_id', 'sequence_no'], 'entry_stop_time_sequence_unique');
            $table->index(['location_id', 'scheduled_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_sheet_entry_stop_times');
    }
};
