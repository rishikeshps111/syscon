<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tripTable = Schema::hasTable('trips') ? 'trips' : 'trip_setups';

        Schema::create('trip_assignments', function (Blueprint $table) use ($tripTable) {
            $table->id();
            $table->foreignId('trip_id')->constrained($tripTable)->cascadeOnDelete();
            $table->date('from_date');
            $table->date('to_date');
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('driver_profile_id')->constrained('driver_profiles')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['trip_id', 'from_date', 'to_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_assignments');
    }
};
