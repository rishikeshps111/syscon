<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_sheet_entry_dors', function (Blueprint $table) {
            if (! Schema::hasColumn('trip_sheet_entry_dors', 'odometer_start_image_path')) {
                $table->string('odometer_start_image_path')->nullable()->after('odometer_start_reading');
            }

            if (! Schema::hasColumn('trip_sheet_entry_dors', 'odometer_end_image_path')) {
                $table->string('odometer_end_image_path')->nullable()->after('odometer_end_reading');
            }
        });
    }

    public function down(): void
    {
        Schema::table('trip_sheet_entry_dors', function (Blueprint $table) {
            if (Schema::hasColumn('trip_sheet_entry_dors', 'odometer_start_image_path')) {
                $table->dropColumn('odometer_start_image_path');
            }

            if (Schema::hasColumn('trip_sheet_entry_dors', 'odometer_end_image_path')) {
                $table->dropColumn('odometer_end_image_path');
            }
        });
    }
};
