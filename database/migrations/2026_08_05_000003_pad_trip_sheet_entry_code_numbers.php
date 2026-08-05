<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $entries = DB::table('trip_sheet_entries')
            ->join('trip_sheets', 'trip_sheet_entries.trip_sheet_id', '=', 'trip_sheets.id')
            ->join('trips', 'trip_sheets.trip_id', '=', 'trips.id')
            ->orderBy('trips.id')
            ->orderBy('trip_sheets.date')
            ->orderBy('trip_sheet_entries.trip_order_sequence_no')
            ->orderBy('trip_sheet_entries.id')
            ->get(['trip_sheet_entries.id', 'trips.id as trip_id', 'trips.code as trip_code']);

        foreach ($entries as $entry) {
            DB::table('trip_sheet_entries')->where('id', $entry->id)->update([
                'code' => '__TRIP_SHEET_ENTRY_'.$entry->id,
            ]);
        }

        $sequences = [];
        foreach ($entries as $entry) {
            $sequence = ($sequences[$entry->trip_id] ?? 0) + 1;
            $sequences[$entry->trip_id] = $sequence;

            DB::table('trip_sheet_entries')->where('id', $entry->id)->update([
                'code' => ($entry->trip_code ?: 'TRIP-'.$entry->trip_id)
                    .'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
            ]);
        }
    }

    public function down(): void
    {
        // Codes remain unique and valid when rolling back this display-format change.
    }
};
