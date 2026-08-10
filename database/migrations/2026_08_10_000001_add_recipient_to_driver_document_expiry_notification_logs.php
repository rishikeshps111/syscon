<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'driver_document_expiry_notification_logs';

    public function up(): void
    {
        if (! Schema::hasIndex(self::TABLE, 'driver_doc_expiry_driver_idx')) {
            Schema::table(self::TABLE, fn (Blueprint $table) => $table->index('driver_profile_id', 'driver_doc_expiry_driver_idx'));
        }

        if (Schema::hasIndex(self::TABLE, 'driver_document_expiry_notification_unique')) {
            Schema::table(self::TABLE, fn (Blueprint $table) => $table->dropUnique('driver_document_expiry_notification_unique'));
        }

        Schema::table(self::TABLE, function (Blueprint $table): void {
            if (! Schema::hasColumn(self::TABLE, 'recipient_user_id')) {
                $table->unsignedBigInteger('recipient_user_id')->nullable()->after('driver_profile_id');
            }
            if (! Schema::hasColumn(self::TABLE, 'recipient_type')) {
                $table->string('recipient_type', 20)->nullable()->after('recipient_user_id');
            }
            if (! Schema::hasColumn(self::TABLE, 'reminder_date')) {
                $table->date('reminder_date')->nullable()->after('expiry_date');
            }
        });

        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->foreign('recipient_user_id', 'driver_doc_expiry_recipient_fk')
                ->references('id')->on('users')->cascadeOnDelete();

            $table->unique(
                ['driver_profile_id', 'recipient_user_id', 'document_type', 'expiry_date', 'reminder_date'],
                'driver_document_expiry_recipient_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table(self::TABLE, function (Blueprint $table): void {
            $table->dropUnique('driver_document_expiry_recipient_unique');
            $table->dropForeign('driver_doc_expiry_recipient_fk');
            $table->dropColumn(['recipient_user_id', 'recipient_type', 'reminder_date']);
            $table->unique(
                ['driver_profile_id', 'document_type', 'expiry_date', 'expiry_status'],
                'driver_document_expiry_notification_unique'
            );
            $table->dropIndex('driver_doc_expiry_driver_idx');
        });
    }
};
