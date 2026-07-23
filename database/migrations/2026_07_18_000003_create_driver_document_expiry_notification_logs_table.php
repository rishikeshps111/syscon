<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_document_expiry_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_profile_id');
            $table->foreign('driver_profile_id', 'driver_doc_expiry_driver_fk')
                ->references('id')
                ->on('driver_profiles')
                ->cascadeOnDelete();
            $table->string('document_type', 20);
            $table->date('expiry_date');
            $table->string('expiry_status', 20);
            $table->unsignedInteger('sent_count')->default(0);
            $table->string('status', 20)->default('pending');
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['driver_profile_id', 'document_type', 'expiry_date', 'expiry_status'],
                'driver_document_expiry_notification_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_document_expiry_notification_logs');
    }
};
