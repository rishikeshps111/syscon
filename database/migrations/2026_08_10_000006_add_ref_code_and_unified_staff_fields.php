<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('ref_code', 100)->nullable()->after('code')->index();
        });

        foreach (['controller_profiles', 'supervisor_profiles'] as $profileTable) {
            Schema::table($profileTable, function (Blueprint $table): void {
                $table->foreignId('reporting_to')->nullable()->after('depot_id')->constrained('users')->nullOnDelete();
            });
        }

        Schema::table('housekeeping_profiles', function (Blueprint $table): void {
            $table->foreignId('reporting_to')->nullable()->after('depot_id')->constrained('users')->nullOnDelete();
            $table->string('father_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('pan_number', 20)->nullable();
            $table->string('uan', 50)->nullable();
            $table->string('esic_wc', 50)->nullable();
            $table->decimal('basic', 12, 2)->nullable();
            $table->decimal('vda', 12, 2)->nullable();
            $table->decimal('basic_vda', 12, 2)->nullable();
            $table->decimal('hra', 12, 2)->nullable();
            $table->decimal('special_allowance', 12, 2)->nullable();
            $table->decimal('conveyance_allowance', 12, 2)->nullable();
            $table->decimal('bonus', 12, 2)->nullable();
            $table->decimal('gross_salary', 12, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('housekeeping_profiles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('reporting_to');
            $table->dropColumn(['father_name', 'date_of_birth', 'pan_number', 'uan', 'esic_wc', 'basic', 'vda', 'basic_vda', 'hra', 'special_allowance', 'conveyance_allowance', 'bonus', 'gross_salary']);
        });
        foreach (['controller_profiles', 'supervisor_profiles'] as $profileTable) {
            Schema::table($profileTable, fn (Blueprint $table) => $table->dropConstrainedForeignId('reporting_to'));
        }
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('ref_code'));
    }
};
