<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supervisor_trip_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supervisor_profile_id')->constrained()->cascadeOnDelete();
            $table->date('trip_date');
            $table->unsignedInteger('trip_count');
            $table->unsignedInteger('sent_count')->default(0);
            $table->string('status', 20)->default('pending');
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['supervisor_profile_id', 'trip_date'], 'supervisor_trip_notification_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisor_trip_notification_logs');
    }
};
