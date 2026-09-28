<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['housekeeping_profiles', 'controller_profiles', 'supervisor_profiles'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('designation_id')->nullable()->after('user_id')->constrained('designations')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['housekeeping_profiles', 'controller_profiles', 'supervisor_profiles'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('designation_id');
            });
        }
    }
};
