<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('trips') || Schema::hasColumn('trips', 'state_id')) {
            return;
        }

        Schema::table('trips', function (Blueprint $table) {
            $table->foreignId('state_id')->nullable()->after('trip_side')->constrained('states')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('trips') || ! Schema::hasColumn('trips', 'state_id')) {
            return;
        }

        Schema::table('trips', function (Blueprint $table) {
            $table->dropConstrainedForeignId('state_id');
        });
    }
};
