<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('driver_profiles', function (Blueprint $table) {
            $table->string('uan', 50)->nullable()->after('joining_date');
            $table->string('wc_policy', 100)->nullable()->after('uan');
            $table->string('pan_number', 20)->nullable()->after('wc_policy');
        });
    }

    public function down(): void
    {
        Schema::table('driver_profiles', function (Blueprint $table) {
            $table->dropColumn(['uan', 'wc_policy', 'pan_number']);
        });
    }
};
