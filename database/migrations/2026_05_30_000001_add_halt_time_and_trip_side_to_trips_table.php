<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('trips')) {
            return;
        }

        Schema::table('trips', function (Blueprint $table) {
            if (! Schema::hasColumn('trips', 'halt_time')) {
                $table->time('halt_time')->nullable()->after('end_time');
            }

            if (! Schema::hasColumn('trips', 'trip_side')) {
                $table->string('trip_side', 20)->nullable()->after('halt_time');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('trips')) {
            return;
        }

        Schema::table('trips', function (Blueprint $table) {
            if (Schema::hasColumn('trips', 'trip_side')) {
                $table->dropColumn('trip_side');
            }

            if (Schema::hasColumn('trips', 'halt_time')) {
                $table->dropColumn('halt_time');
            }
        });
    }
};
