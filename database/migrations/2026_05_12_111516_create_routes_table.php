<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained('states')->onDelete('cascade');
            $table->foreignId('start_point_id')->constrained('depots')->onDelete('cascade');
            $table->foreignId('end_point_id')->constrained('depots')->onDelete('cascade');
            $table->string('code')->nullable()->unique();
            $table->string('name');
            $table->integer('distance')->nullable();
            $table->time('estimated_duration')->nullable();
            $table->enum('route_type', ['Intracity', 'intercity'])->default('Intracity');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};
