<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_sheet_entries', function (Blueprint $table) {
            $table->string('code')->nullable()->after('id');
        });

        $sequences = [];
        DB::table('trip_sheet_entries')
            ->join('trip_sheets', 'trip_sheet_entries.trip_sheet_id', '=', 'trip_sheets.id')
            ->join('trips', 'trip_sheets.trip_id', '=', 'trips.id')
            ->orderBy('trips.id')
            ->orderBy('trip_sheets.date')
            ->orderBy('trip_sheet_entries.trip_order_sequence_no')
            ->orderBy('trip_sheet_entries.id')
            ->get(['trip_sheet_entries.id', 'trips.id as trip_id', 'trips.code as trip_code'])
            ->each(function ($entry) use (&$sequences): void {
                $sequence = ($sequences[$entry->trip_id] ?? 0) + 1;
                $sequences[$entry->trip_id] = $sequence;

                DB::table('trip_sheet_entries')->where('id', $entry->id)->update([
                    'code' => ($entry->trip_code ?: 'TRIP-'.$entry->trip_id).'-'.$sequence,
                ]);
            });

        Schema::table('trip_sheet_entries', function (Blueprint $table) {
            $table->string('code')->nullable(false)->change();
            $table->unique('code');
        });
    }

    public function down(): void
    {
        Schema::table('trip_sheet_entries', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }
};
