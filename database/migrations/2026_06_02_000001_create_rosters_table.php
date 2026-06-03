<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rosters', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable()->unique();
            $table->foreignId('state_id')->constrained()->restrictOnDelete();
            $table->foreignId('oem_id')->constrained('oems')->restrictOnDelete();
            $table->foreignId('depot_id')->constrained()->restrictOnDelete();
            $table->date('duty_date');
            $table->string('shift_type', 20);
            $table->time('shift_start_time');
            $table->time('shift_end_time');
            $table->foreignId('trip_sheet_entry_id')->constrained()->restrictOnDelete();
            $table->foreignId('trip_assignment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('driver_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supervisor_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('controller_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->time('reporting_time')->nullable();
            $table->text('remarks')->nullable();
            $table->string('status', 20)->default('assigned');
            $table->string('attendance_status', 20)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['duty_date', 'shift_type']);
            $table->index(['state_id', 'oem_id', 'depot_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rosters');
    }
};
