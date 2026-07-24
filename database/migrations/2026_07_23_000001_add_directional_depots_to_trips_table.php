<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->foreignId('from_depot_id')
                ->nullable()
                ->after('depot_id')
                ->constrained('depots')
                ->nullOnDelete();
            $table->foreignId('to_depot_id')
                ->nullable()
                ->after('from_depot_id')
                ->constrained('depots')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropConstrainedForeignId('to_depot_id');
            $table->dropConstrainedForeignId('from_depot_id');
        });
    }
};
