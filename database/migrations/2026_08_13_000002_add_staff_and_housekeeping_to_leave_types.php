<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->enum('applicable_for', [
                'all_employees', 'drivers', 'staff', 'housekeeping', 'controllers', 'supervisors',
            ])->default('all_employees')->change();
        });
    }

    public function down(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->enum('applicable_for', [
                'all_employees', 'drivers', 'controllers', 'supervisors',
            ])->default('all_employees')->change();
        });
    }
};
