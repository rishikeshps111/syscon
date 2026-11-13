<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_processing_items', function (Blueprint $table) {
            $table->decimal('employer_esi', 12, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('salary_processing_items', function (Blueprint $table) {
            $table->dropColumn('employer_esi');
        });
    }
};
