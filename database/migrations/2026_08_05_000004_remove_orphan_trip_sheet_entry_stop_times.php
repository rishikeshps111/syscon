<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('trip_sheet_entry_stop_times')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('trip_sheet_entries')
                    ->whereColumn('trip_sheet_entries.id', 'trip_sheet_entry_stop_times.trip_sheet_entry_id');
            })
            ->delete();
    }

    public function down(): void
    {
        // Orphan records cannot be restored because their parent entries no longer exist.
    }
};
