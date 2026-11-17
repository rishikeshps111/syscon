<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_processing_items', function (Blueprint $table) {
            $table->decimal('total_working_days', 10, 2)->default(0)->change();
            $table->decimal('total_shifts_completed', 12, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        // Keep decimal storage on rollback so saved fractional shifts are not lost.
    }
};
