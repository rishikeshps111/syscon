<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('housekeeping_profiles', function (Blueprint $table): void {
            $table->enum('employment_type', ['permanent', 'full_time', 'part_time', 'contract'])->change();
        });
        DB::table('housekeeping_profiles')->where('employment_type', 'permanent')->update(['employment_type' => 'full_time']);
        Schema::table('housekeeping_profiles', function (Blueprint $table): void {
            $table->enum('employment_type', ['full_time', 'part_time', 'contract'])->change();
            $table->string('pincode', 10)->nullable()->change();
            $table->text('address')->nullable()->change();
            $table->foreignId('branch_location_id')->nullable()->change();
            $table->string('emergency_contact_name')->nullable()->change();
            $table->string('emergency_country_code')->nullable()->change();
            $table->string('emergency_contact_no')->nullable()->change();
            $table->date('medical_fitness_expiry')->nullable()->change();
        });
    }

    public function down(): void
    {
        // The former housekeeping-only fields cannot safely be made required again for unified records.
    }
};
