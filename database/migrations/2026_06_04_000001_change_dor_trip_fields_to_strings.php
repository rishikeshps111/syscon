<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_sheet_entry_dors', function (Blueprint $table) {
            $table->string('schedule_trip')->nullable()->change();
            $table->string('actual_trip')->nullable()->change();
            $table->string('miss_trip')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('trip_sheet_entry_dors', function (Blueprint $table) {
            $table->unsignedInteger('schedule_trip')->nullable()->change();
            $table->unsignedInteger('actual_trip')->nullable()->change();
            $table->unsignedInteger('miss_trip')->nullable()->change();
        });
    }
};
