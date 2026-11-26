<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_sheet_entry_dors', function (Blueprint $table) {
            $table->string('start_image')->nullable()->after('odometer_start_image_path');
            $table->string('end_image')->nullable()->after('odometer_end_image_path');
        });
    }

    public function down(): void
    {
        Schema::table('trip_sheet_entry_dors', function (Blueprint $table) {
            $table->dropColumn(['start_image', 'end_image']);
        });
    }
};
