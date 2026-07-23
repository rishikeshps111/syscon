<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_trip_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_profile_id')->constrained()->cascadeOnDelete();
            $table->date('trip_date');
            $table->unsignedInteger('trip_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->string('status', 20)->default('pending');
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['driver_profile_id', 'trip_date'], 'driver_trip_notification_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_trip_notification_logs');
    }
};
