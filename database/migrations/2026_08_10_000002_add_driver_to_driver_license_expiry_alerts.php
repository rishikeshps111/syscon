<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('driver_license_expiry_alerts', function (Blueprint $table): void {
            $table->foreignId('driver_profile_id')->nullable()->after('user_id');
            $table->foreign('driver_profile_id', 'driver_license_alert_driver_fk')
                ->references('id')->on('driver_profiles')->cascadeOnDelete();
            $table->date('expiry_date')->nullable()->after('driver_profile_id');
            $table->index(
                ['user_id', 'driver_profile_id', 'expiry_date', 'notified_at'],
                'driver_license_alert_recipient_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('driver_license_expiry_alerts', function (Blueprint $table): void {
            $table->dropIndex('driver_license_alert_recipient_idx');
            $table->dropForeign('driver_license_alert_driver_fk');
            $table->dropColumn(['driver_profile_id', 'expiry_date']);
        });
    }
};
