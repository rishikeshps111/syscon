<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('depot_branch_location')) {
            return;
        }

        Schema::create('depot_branch_location', function (Blueprint $table) {
            $table->id();
            $table->foreignId('depot_id')->constrained('depots')->cascadeOnDelete();
            $table->foreignId('branch_location_id')->constrained('branch_locations')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['depot_id', 'branch_location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depot_branch_location');
    }
};
