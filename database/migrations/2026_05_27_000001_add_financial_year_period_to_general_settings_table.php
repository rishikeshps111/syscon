<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('general_settings', 'financial_year_from_month')) {
                $table->unsignedTinyInteger('financial_year_from_month')->nullable()->after('financial_year');
            }

            if (! Schema::hasColumn('general_settings', 'financial_year_to_year')) {
                $table->unsignedSmallInteger('financial_year_to_year')->nullable()->after('financial_year_from_month');
            }

            if (! Schema::hasColumn('general_settings', 'financial_year_to_month')) {
                $table->unsignedTinyInteger('financial_year_to_month')->nullable()->after('financial_year_to_year');
            }
        });

        DB::table('general_settings')
            ->whereNotNull('financial_year')
            ->whereNull('financial_year_from_month')
            ->get(['id', 'financial_year'])
            ->each(function ($setting) {
                $year = (int) $setting->financial_year;

                if ($year < 1900 || $year > 2100) {
                    return;
                }

                DB::table('general_settings')
                    ->where('id', $setting->id)
                    ->update([
                        'financial_year_from_month' => 4,
                        'financial_year_to_year' => $year + 1,
                        'financial_year_to_month' => 3,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            foreach ([
                'financial_year_to_month',
                'financial_year_to_year',
                'financial_year_from_month',
            ] as $column) {
                if (Schema::hasColumn('general_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
