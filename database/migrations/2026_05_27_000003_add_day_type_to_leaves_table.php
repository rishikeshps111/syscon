<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            if (! Schema::hasColumn('leaves', 'day_type')) {
                $table->enum('day_type', ['full_day', 'half_day'])->default('full_day')->after('leave_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            if (Schema::hasColumn('leaves', 'day_type')) {
                $table->dropColumn('day_type');
            }
        });
    }
};
