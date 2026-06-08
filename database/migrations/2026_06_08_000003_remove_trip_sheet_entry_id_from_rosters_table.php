<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rosters', function (Blueprint $table) {
            if (Schema::hasColumn('rosters', 'trip_sheet_entry_id')) {
                $table->dropConstrainedForeignId('trip_sheet_entry_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rosters', function (Blueprint $table) {
            if (! Schema::hasColumn('rosters', 'trip_sheet_entry_id')) {
                $table->foreignId('trip_sheet_entry_id')->nullable()->constrained()->restrictOnDelete();
            }
        });
    }
};
