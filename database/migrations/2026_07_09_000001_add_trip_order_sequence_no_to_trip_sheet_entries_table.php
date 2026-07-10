<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_sheet_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('trip_sheet_entries', 'trip_order_sequence_no')) {
                $table->unsignedInteger('trip_order_sequence_no')->nullable()->after('vehicle_id')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('trip_sheet_entries', function (Blueprint $table) {
            if (Schema::hasColumn('trip_sheet_entries', 'trip_order_sequence_no')) {
                $table->dropColumn('trip_order_sequence_no');
            }
        });
    }
};
