<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_license_expiry_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('expired_count')->default(0);
            $table->timestamp('notified_at')->index();
            $table->timestamps();

            $table->index(['user_id', 'notified_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_license_expiry_alerts');
    }
};
