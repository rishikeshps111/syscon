<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roster_trip_sheet_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roster_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trip_sheet_entry_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['roster_id', 'trip_sheet_entry_id'], 'roster_trip_sheet_entry_unique');
            $table->index('trip_sheet_entry_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roster_trip_sheet_entries');
    }
};
