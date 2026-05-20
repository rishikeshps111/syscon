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
        Schema::create('oem_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('oem_id')
                ->constrained('oems')
                ->cascadeOnDelete();
            $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->enum('address_type', [
                'HQ',
                'Billing',
                'Service',
                'Depot'
            ]);
            $table->string('address_line1');
            $table->string('address_line2')->nullable();
            $table->string('pincode', 10)->nullable();
            $table->string('latitude', 20)->nullable();
            $table->string('longitude', 20)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oem_addresses');
    }
};
