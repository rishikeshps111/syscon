<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_classifications', function (Blueprint $table) {
            $table->renameColumn('name', 'title');
        });

        Schema::table('vehicle_classifications', function (Blueprint $table) {
            $table->dropColumn(['code', 'capacity', 'fuel_type']);
        });

        Schema::create('trip_natures', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique();
            $table->longText('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_natures');

        Schema::table('vehicle_classifications', function (Blueprint $table) {
            $table->string('code')->nullable()->unique();
            $table->integer('capacity')->nullable();
            $table->enum('fuel_type', ['petrol', 'diesel', 'ev', 'hybrid'])->nullable();
        });

        Schema::table('vehicle_classifications', function (Blueprint $table) {
            $table->renameColumn('title', 'name');
        });
    }
};
