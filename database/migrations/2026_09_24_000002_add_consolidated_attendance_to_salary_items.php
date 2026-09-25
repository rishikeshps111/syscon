<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_processing_items', function (Blueprint $table) {
            $table->decimal('present_days', 8, 2)->default(0)->after('total_leave_taken');
            $table->decimal('week_off_days', 8, 2)->default(0)->after('present_days');
            $table->decimal('absent_days', 8, 2)->default(0)->after('week_off_days');
            $table->decimal('total_attendance_days', 8, 2)->default(0)->after('absent_days');
        });
    }

    public function down(): void
    {
        Schema::table('salary_processing_items', function (Blueprint $table) {
            $table->dropColumn(['present_days', 'week_off_days', 'absent_days', 'total_attendance_days']);
        });
    }
};
