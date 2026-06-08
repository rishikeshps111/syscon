<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('rosters')
            ->whereNotNull('trip_sheet_entry_id')
            ->select(['id', 'trip_sheet_entry_id'])
            ->orderBy('id')
            ->get()
            ->each(function ($roster) use ($now): void {
                DB::table('roster_trip_sheet_entries')->insertOrIgnore([
                    'roster_id' => $roster->id,
                    'trip_sheet_entry_id' => $roster->trip_sheet_entry_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }

    public function down(): void
    {
        //
    }
};
