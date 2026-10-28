<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Run with attendance imports paused while the constraint is installed.
        DB::transaction(function () {
            $groups = DB::table('attendance_consolidate_imports')
                ->select('year', 'month', 'depot_id')->selectRaw('MAX(id) AS retained_id')
                ->groupBy('year', 'month', 'depot_id')->havingRaw('COUNT(*) > 1')->get();

            foreach ($groups as $group) {
                $ids = DB::table('attendance_consolidate_imports')
                    ->where('year', $group->year)->where('month', $group->month)
                    ->where('depot_id', $group->depot_id)->where('id', '<>', $group->retained_id)->pluck('id');
                DB::table('salary_processings')->whereIn('attendance_consolidate_import_id', $ids)
                    ->update(['attendance_consolidate_import_id' => $group->retained_id]);
                DB::table('attendance_consolidate_rows')->whereIn('import_id', $ids)->delete();
                DB::table('attendance_consolidate_imports')->whereIn('id', $ids)->delete();
            }
        });

        Schema::table('attendance_consolidate_imports', function (Blueprint $table) {
            $table->unique(['year', 'month', 'depot_id'], 'attendance_consolidate_unique_period');
            $table->dropUnique('attendance_consolidate_unique_file');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_consolidate_imports', function (Blueprint $table) {
            $table->unique(['year', 'month', 'depot_id', 'file_checksum'], 'attendance_consolidate_unique_file');
            $table->dropUnique('attendance_consolidate_unique_period');
        });
    }
};
