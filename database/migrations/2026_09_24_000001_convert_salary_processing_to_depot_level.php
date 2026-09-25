<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_processings', function (Blueprint $table) {
            $table->foreignId('attendance_consolidate_import_id')
                ->nullable()
                ->after('depot_id')
                ->constrained('attendance_consolidate_imports')
                ->nullOnDelete();
            $table->dropUnique('salary_processing_period_unique');
            $table->unique(['year', 'month', 'depot_id'], 'salary_processing_depot_period_unique');
        });
    }

    public function down(): void
    {
        Schema::table('salary_processings', function (Blueprint $table) {
            $table->dropUnique('salary_processing_depot_period_unique');
            $table->dropForeign(['attendance_consolidate_import_id']);
            $table->dropColumn('attendance_consolidate_import_id');
            $table->unique(['year', 'month', 'depot_id', 'role_id'], 'salary_processing_period_unique');
        });
    }
};
