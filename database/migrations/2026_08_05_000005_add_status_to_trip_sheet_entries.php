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
            $table->string('status')->default('pending')->after('code')->index();
        });

        DB::table('trip_sheet_entries')
            ->join('trip_sheets', 'trip_sheet_entries.trip_sheet_id', '=', 'trip_sheets.id')
            ->get(['trip_sheet_entries.id', 'trip_sheets.status'])
            ->each(function ($entry): void {
                DB::table('trip_sheet_entries')->where('id', $entry->id)->update([
                    'status' => $entry->status,
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('trip_sheet_entries', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
