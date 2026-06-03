<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_sheet_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('trip_sheet_entries', 'driver_profile_id')) {
                $table->foreignId('driver_profile_id')
                    ->nullable()
                    ->after('actual_reach_time')
                    ->constrained('driver_profiles')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('trip_sheet_entries', 'vehicle_id')) {
                $table->foreignId('vehicle_id')
                    ->nullable()
                    ->after('driver_profile_id')
                    ->constrained('vehicles')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('trip_sheet_entries', function (Blueprint $table) {
            if (Schema::hasColumn('trip_sheet_entries', 'vehicle_id')) {
                $table->dropConstrainedForeignId('vehicle_id');
            }

            if (Schema::hasColumn('trip_sheet_entries', 'driver_profile_id')) {
                $table->dropConstrainedForeignId('driver_profile_id');
            }
        });
    }
};
