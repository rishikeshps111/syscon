<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->enum('leave_for', ['general', 'driver']);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->nullable()->constrained('leave_types')->nullOnDelete();
            $table->string('driver_leave_type')->nullable();
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->date('leave_date')->nullable();
            $table->decimal('number_of_days', 8, 2)->nullable();
            $table->string('shift')->nullable();
            $table->string('assigned_vehicle_route')->nullable();
            $table->string('attachment_path')->nullable();
            $table->text('reason')->nullable();
            $table->text('remarks')->nullable();
            $table->enum('status', ['Pending', 'Approved', 'Rejected', 'Cancelled', 'Auto Marked'])->default('Pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['leave_for', 'status']);
            $table->index(['user_id', 'from_date', 'leave_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};
