<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->enum('status', ['present', 'absent', 'half_day', 'week_off'])->default('present')->change();
            $table->string('duty_type')->nullable()->after('half_day_period');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('duty_type');
            $table->enum('status', ['present', 'absent', 'half_day'])->default('present')->change();
        });
    }
};
