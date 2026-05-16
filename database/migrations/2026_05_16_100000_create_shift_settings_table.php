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
        Schema::create('shift_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('number_of_shifts_per_day')->default(2);
            $table->string('code')->nullable()->unique();
            $table->enum('shift_name', ['Morning Shift', 'Evening Shift']);
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('break_duration_minutes')->default(0);
            $table->decimal('total_working_hours', 5, 2);
            $table->unsignedInteger('grace_time_minutes')->default(0);
            $table->decimal('minimum_working_hours', 5, 2);
            $table->boolean('check_in_window_start')->default(false);
            $table->boolean('check_in_window_end')->default(false);
            $table->boolean('check_out_flexibility')->default(false);
            $table->boolean('enable_overtime')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_settings');
    }
};
