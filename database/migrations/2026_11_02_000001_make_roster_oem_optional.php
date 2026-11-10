<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rosters', function (Blueprint $table) {
            $table->foreignId('oem_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('rosters')->whereNull('oem_id')->exists()) {
            throw new RuntimeException('Cannot require roster OEM while driver-only rosters have no OEM. Assign an OEM before rolling back.');
        }
        Schema::table('rosters', function (Blueprint $table) {
            $table->foreignId('oem_id')->nullable(false)->change();
        });
    }
};
