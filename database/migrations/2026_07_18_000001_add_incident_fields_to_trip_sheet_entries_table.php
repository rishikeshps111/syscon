<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_sheet_entries', function (Blueprint $table) {
            $table->boolean('energy_status')->default(false)->after('vehicle_condition');
            $table->boolean('accident_status')->default(false)->after('energy_status');
            $table->text('accident_remarks')->nullable()->after('accident_status');
            $table->boolean('vehicle_breakdown')->default(false)->after('accident_remarks');
            $table->boolean('medical_emergency')->default(false)->after('vehicle_breakdown');
            $table->boolean('passenger_issue')->default(false)->after('medical_emergency');
            $table->boolean('security_threat')->default(false)->after('passenger_issue');
        });
    }

    public function down(): void
    {
        Schema::table('trip_sheet_entries', function (Blueprint $table) {
            $table->dropColumn([
                'energy_status',
                'accident_status',
                'accident_remarks',
                'vehicle_breakdown',
                'medical_emergency',
                'passenger_issue',
                'security_threat',
            ]);
        });
    }
};
