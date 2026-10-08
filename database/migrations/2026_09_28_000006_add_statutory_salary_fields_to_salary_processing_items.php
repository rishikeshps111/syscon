<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_processing_items', function (Blueprint $table) {
            $table->decimal('extra_days_worked', 8, 2)->default(0)->after('total_attendance_days');
            $table->decimal('gross_salary', 12, 2)->default(0)->after('basic_salary');
            $table->decimal('earned_salary', 12, 2)->default(0)->after('gross_salary');
            $table->decimal('extra_duty_incentive', 12, 2)->default(0)->after('earned_salary');
            $table->decimal('total_earned', 12, 2)->default(0)->after('extra_duty_incentive');
            $table->decimal('pf', 12, 2)->default(0)->after('total_earned');
            $table->decimal('professional_tax', 12, 2)->default(0)->after('pf');
            $table->decimal('esi', 12, 2)->default(0)->after('professional_tax');
            $table->decimal('total_deduction', 12, 2)->default(0)->after('esi');
            $table->decimal('net_total', 12, 2)->default(0)->after('total_deduction');
        });
    }

    public function down(): void
    {
        Schema::table('salary_processing_items', function (Blueprint $table) {
            $table->dropColumn([
                'extra_days_worked', 'gross_salary', 'earned_salary', 'extra_duty_incentive',
                'total_earned', 'pf', 'professional_tax', 'esi', 'total_deduction', 'net_total',
            ]);
        });
    }
};
