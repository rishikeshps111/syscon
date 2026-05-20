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
        Schema::create('oems', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')
                ->constrained('states')
                ->cascadeOnDelete();
            $table->string('oem_code', 50)->nullable();
            $table->string('oem_name', 255);
            $table->string('short_name', 100)->nullable();
            $table->enum('oem_type', [
                'Manufacturer',
                'Service Provider',
                'Dealer'
            ]);
            $table->enum('registration_type', [
                'Company',
                'Partnership',
                'Proprietor'
            ]);
            $table->string('gst_number', 20);
            $table->string('pan_number', 15);
            $table->string('cin_number', 25)->nullable();
            $table->enum('status', [
                'Active',
                'Inactive',
                'Blocked'
            ])->default('Active');
            $table->boolean('is_verified')->default(false);
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->text('remarks')->nullable();
            $table->unique(['state_id', 'oem_code']);
            $table->index('gst_number');
            $table->index('pan_number');
            $table->index('status');
            $table->foreign('verified_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oems');
    }
};
