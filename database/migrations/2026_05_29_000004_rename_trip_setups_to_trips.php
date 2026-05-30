<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('trip_setups') && ! Schema::hasTable('trips')) {
            Schema::rename('trip_setups', 'trips');
        }

        if (Schema::hasTable('trip_assignments') && Schema::hasColumn('trip_assignments', 'trip_setup_id')) {
            Schema::table('trip_assignments', function (Blueprint $table) {
                $table->renameColumn('trip_setup_id', 'trip_id');
            });
        }

        if (Schema::hasTable('trip_sheet_entries') && Schema::hasColumn('trip_sheet_entries', 'trip_setup_id')) {
            Schema::table('trip_sheet_entries', function (Blueprint $table) {
                $table->renameColumn('trip_setup_id', 'trip_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('trip_assignments') && Schema::hasColumn('trip_assignments', 'trip_id')) {
            Schema::table('trip_assignments', function (Blueprint $table) {
                $table->renameColumn('trip_id', 'trip_setup_id');
            });
        }

        if (Schema::hasTable('trip_sheet_entries') && Schema::hasColumn('trip_sheet_entries', 'trip_id')) {
            Schema::table('trip_sheet_entries', function (Blueprint $table) {
                $table->renameColumn('trip_id', 'trip_setup_id');
            });
        }

        if (Schema::hasTable('trips') && ! Schema::hasTable('trip_setups')) {
            Schema::rename('trips', 'trip_setups');
        }
    }
};
