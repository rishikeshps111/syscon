<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_sheet_entries', function (Blueprint $table) {
            $table->string('service_code')->nullable()->after('trip_order_sequence_no');
            $table->unsignedInteger('round_no')->nullable()->after('service_code');
            $table->string('trip_nature')->nullable()->after('round_no');
            $table->decimal('schedule_km', 10, 2)->nullable()->after('trip_nature');

            $table->index(['service_code', 'round_no']);
        });
    }

    public function down(): void
    {
        Schema::table('trip_sheet_entries', function (Blueprint $table) {
            $table->dropIndex(['service_code', 'round_no']);
            $table->dropColumn(['service_code', 'round_no', 'trip_nature', 'schedule_km']);
        });
    }
};
