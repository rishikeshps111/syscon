<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_sheet_entry_dors', function (Blueprint $table) {
            if (! Schema::hasColumn('trip_sheet_entry_dors', 'is_completed')) {
                $table->boolean('is_completed')->default(false)->after('model_9m_12m');
            }
        });
    }

    public function down(): void
    {
        Schema::table('trip_sheet_entry_dors', function (Blueprint $table) {
            if (Schema::hasColumn('trip_sheet_entry_dors', 'is_completed')) {
                $table->dropColumn('is_completed');
            }
        });
    }
};
