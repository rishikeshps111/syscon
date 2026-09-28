<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('housekeeping_profiles', function (Blueprint $table) {
            $table->string('pincode', 10)->nullable()->change();
            $table->text('address')->nullable()->change();
            $foreignKey = $this->branchLocationForeignKey();
            if ($foreignKey) {
                $table->dropForeign($foreignKey);
            }
            $table->unsignedBigInteger('branch_location_id')->nullable()->change();
            $table->foreign('branch_location_id')->references('id')->on('branch_locations')->restrictOnDelete();
            $table->string('emergency_contact_name')->nullable()->change();
            $table->string('emergency_contact_no')->nullable()->change();
            $table->date('medical_fitness_expiry')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('housekeeping_profiles', function (Blueprint $table) {
            $table->string('pincode', 10)->nullable(false)->change();
            $table->text('address')->nullable(false)->change();
            $foreignKey = $this->branchLocationForeignKey();
            if ($foreignKey) {
                $table->dropForeign($foreignKey);
            }
            $table->unsignedBigInteger('branch_location_id')->nullable(false)->change();
            $table->foreign('branch_location_id')->references('id')->on('branch_locations')->restrictOnDelete();
            $table->string('emergency_contact_name')->nullable(false)->change();
            $table->string('emergency_contact_no')->nullable(false)->change();
            $table->date('medical_fitness_expiry')->nullable(false)->change();
        });
    }

    private function branchLocationForeignKey(): ?string
    {
        $constraint = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1',
            ['housekeeping_profiles', 'branch_location_id']
        );

        return $constraint?->CONSTRAINT_NAME;
    }
};
