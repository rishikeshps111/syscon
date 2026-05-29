<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            if (Schema::hasColumn('general_settings', 'financial_year_from_year')) {
                $table->dropColumn('financial_year_from_year');
            }
        });
    }

    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('general_settings', 'financial_year_from_year')) {
                $table->unsignedSmallInteger('financial_year_from_year')->nullable()->after('financial_year');
            }
        });
    }
};
