<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oem_state_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('oem_id')->constrained('oems')->cascadeOnDelete();
            $table->foreignId('state_id')->constrained('states')->restrictOnDelete();
            $table->string('gst_number');
            $table->boolean('is_primary')->default(false);
            $table->boolean('status')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oem_state_mappings');
    }
};
