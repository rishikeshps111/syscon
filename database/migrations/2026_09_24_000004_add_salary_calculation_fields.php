<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_processing_items', function (Blueprint $table) {
            $table->decimal('salary_day_rate', 12, 2)->default(0)->after('actual_worked_days');
            $table->decimal('template_deduction', 12, 2)->default(0)->after('salary_day_rate');
            $table->decimal('lop_deduction', 12, 2)->default(0)->after('template_deduction');
        });
    }

    public function down(): void
    {
        Schema::table('salary_processing_items', function (Blueprint $table) {
            $table->dropColumn(['salary_day_rate', 'template_deduction', 'lop_deduction']);
        });
    }
};
