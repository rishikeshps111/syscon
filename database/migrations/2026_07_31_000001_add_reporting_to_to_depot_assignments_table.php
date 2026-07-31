<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('depot_assignments', function (Blueprint $table) {
            $table->foreignId('reporting_to')
                ->nullable()
                ->after('depot_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('depot_assignments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reporting_to');
        });
    }
};
