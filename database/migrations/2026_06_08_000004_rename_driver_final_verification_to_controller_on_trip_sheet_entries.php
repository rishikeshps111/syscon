<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_sheet_entries', function (Blueprint $table) {
            if (Schema::hasColumn('trip_sheet_entries', 'is_verified_by_driver')
                && ! Schema::hasColumn('trip_sheet_entries', 'is_verified_by_controller')) {
                $table->renameColumn('is_verified_by_driver', 'is_verified_by_controller');
            }

            if (Schema::hasColumn('trip_sheet_entries', 'verified_by_driver')
                && ! Schema::hasColumn('trip_sheet_entries', 'verified_by_controller')) {
                $table->renameColumn('verified_by_driver', 'verified_by_controller');
            }

            if (Schema::hasColumn('trip_sheet_entries', 'verified_by_driver_at')
                && ! Schema::hasColumn('trip_sheet_entries', 'verified_by_controller_at')) {
                $table->renameColumn('verified_by_driver_at', 'verified_by_controller_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('trip_sheet_entries', function (Blueprint $table) {
            if (Schema::hasColumn('trip_sheet_entries', 'is_verified_by_controller')
                && ! Schema::hasColumn('trip_sheet_entries', 'is_verified_by_driver')) {
                $table->renameColumn('is_verified_by_controller', 'is_verified_by_driver');
            }

            if (Schema::hasColumn('trip_sheet_entries', 'verified_by_controller')
                && ! Schema::hasColumn('trip_sheet_entries', 'verified_by_driver')) {
                $table->renameColumn('verified_by_controller', 'verified_by_driver');
            }

            if (Schema::hasColumn('trip_sheet_entries', 'verified_by_controller_at')
                && ! Schema::hasColumn('trip_sheet_entries', 'verified_by_driver_at')) {
                $table->renameColumn('verified_by_controller_at', 'verified_by_driver_at');
            }
        });
    }
};
