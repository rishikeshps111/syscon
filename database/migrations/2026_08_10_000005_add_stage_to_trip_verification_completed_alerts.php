<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_verification_completed_alerts', function (Blueprint $table): void {
            $table->index('user_id', 'trip_verification_alert_user_idx');
        });

        Schema::table('trip_verification_completed_alerts', function (Blueprint $table): void {
            $table->dropUnique('trip_verification_admin_entry_unique');
            $table->string('verification_stage', 20)->default('final')->after('trip_sheet_entry_id');
            $table->unique(
                ['user_id', 'trip_sheet_entry_id', 'verification_stage'],
                'trip_verification_admin_entry_stage_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('trip_verification_completed_alerts', function (Blueprint $table): void {
            $table->dropUnique('trip_verification_admin_entry_stage_unique');
            $table->dropColumn('verification_stage');
            $table->unique(['user_id', 'trip_sheet_entry_id'], 'trip_verification_admin_entry_unique');
        });

        Schema::table('trip_verification_completed_alerts', function (Blueprint $table): void {
            $table->dropIndex('trip_verification_alert_user_idx');
        });
    }
};
