<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('driver_license_expiry_alerts', function (Blueprint $table): void {
            $table->string('document_type', 20)->default('licence')->after('driver_profile_id');
            $table->index(
                ['user_id', 'driver_profile_id', 'document_type', 'expiry_date', 'notified_at'],
                'driver_document_admin_alert_idx'
            );
        });

        DB::table('driver_license_expiry_alerts')->whereNull('document_type')->update(['document_type' => 'licence']);
    }

    public function down(): void
    {
        Schema::table('driver_license_expiry_alerts', function (Blueprint $table): void {
            $table->dropIndex('driver_document_admin_alert_idx');
            $table->dropColumn('document_type');
        });
    }
};
