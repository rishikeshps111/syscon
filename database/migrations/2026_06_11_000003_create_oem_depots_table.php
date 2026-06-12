<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('oem_depots')) {
            return;
        }

        Schema::create('oem_depots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('oem_id')->constrained('oems')->cascadeOnDelete();
            $table->foreignId('depot_id')->constrained('depots')->cascadeOnDelete();
            $table->foreignId('branch_location_id')->constrained('branch_locations')->cascadeOnDelete();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['oem_id', 'depot_id', 'branch_location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oem_depots');
    }
};
