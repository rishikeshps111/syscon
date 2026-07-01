<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_sheet_entry_dors', function (Blueprint $table) {
            if (! Schema::hasColumn('trip_sheet_entry_dors', 'route_start_soc_percent_image')) {
                $table->string('route_start_soc_percent_image')->nullable()->after('route_start_soc_percent');
            }

            if (! Schema::hasColumn('trip_sheet_entry_dors', 'route_end_soc_percent_image')) {
                $table->string('route_end_soc_percent_image')->nullable()->after('route_end_soc_percent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('trip_sheet_entry_dors', function (Blueprint $table) {
            if (Schema::hasColumn('trip_sheet_entry_dors', 'route_start_soc_percent_image')) {
                $table->dropColumn('route_start_soc_percent_image');
            }

            if (Schema::hasColumn('trip_sheet_entry_dors', 'route_end_soc_percent_image')) {
                $table->dropColumn('route_end_soc_percent_image');
            }
        });
    }
};
