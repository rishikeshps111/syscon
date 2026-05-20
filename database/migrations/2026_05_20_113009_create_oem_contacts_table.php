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
        Schema::create('oem_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('oem_id')
                ->constrained('oems')
                ->cascadeOnDelete();
            $table->string('contact_person');
            $table->string('designation')->nullable();
            $table->string('phone', 20);
            $table->string('phone_country_code', 5)->nullable();
            $table->string('alternate_phone', 20)->nullable();
            $table->string('alternate_phone_country_code', 5)->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oem_contacts');
    }
};
