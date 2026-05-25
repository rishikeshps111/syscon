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
            $table->foreignId('district_id')->constrained('districts')->onDelete('cascade');
            $table->string('route_code', 50)->nullable();
            $table->string('route_name');
            $table->foreignId('start_point_id')->constrained('locations')->onDelete('cascade');
            $table->foreignId('end_point_id')->constrained('locations')->onDelete('cascade');
            $table->decimal('total_distance_km', 10, 2)->nullable();
            $table->time('estimated_duration')->nullable();
            $table->enum('route_type', ['Intercity', 'Intracity'])->default('Intracity');
            $table->enum('route_category', ['Passenger', 'Cargo'])->default('Passenger');
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['state_id', 'route_code']);
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
