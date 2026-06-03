<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rosters', function (Blueprint $table) {
            if (! Schema::hasColumn('rosters', 'reporting_to_time')) {
                $table->time('reporting_to_time')->nullable()->after('reporting_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rosters', function (Blueprint $table) {
            if (Schema::hasColumn('rosters', 'reporting_to_time')) {
                $table->dropColumn('reporting_to_time');
            }
        });
    }
};
