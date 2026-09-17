<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_sheet_entry_dors', function (Blueprint $table) {
            if (! Schema::hasColumn('trip_sheet_entry_dors', 'vp1_image')) {
                $table->string('vp1_image')->nullable()->after('vp1');
            }
            if (! Schema::hasColumn('trip_sheet_entry_dors', 'dp_image')) {
                $table->string('dp_image')->nullable()->after('dp');
            }
        });
    }

    public function down(): void
    {
        Schema::table('trip_sheet_entry_dors', function (Blueprint $table) {
            $columns = array_filter(['vp1_image', 'dp_image'], fn ($column) => Schema::hasColumn('trip_sheet_entry_dors', $column));
            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
