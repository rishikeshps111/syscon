<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_processing_items', function (Blueprint $table) {
            foreach (['tds', 'salary_advance', 'lwf', 'penalty'] as $field) {
                $table->decimal($field, 12, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('salary_processing_items', function (Blueprint $table) {
            $table->dropColumn(['tds', 'salary_advance', 'lwf', 'penalty']);
        });
    }
};
