<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_verification_completed_alerts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trip_sheet_entry_id')->constrained('trip_sheet_entries')->cascadeOnDelete();
            $table->timestamp('notified_at')->index();
            $table->timestamps();
            $table->unique(['user_id', 'trip_sheet_entry_id'], 'trip_verification_admin_entry_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_verification_completed_alerts');
    }
};
