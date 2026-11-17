<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rosters', function (Blueprint $table): void {
            $table->boolean('inherit_trip_assignment')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('rosters', fn (Blueprint $table) => $table->dropColumn('inherit_trip_assignment'));
    }
};
