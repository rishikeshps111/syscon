<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->date('attendance_date');
            $table->unsignedSmallInteger('month');
            $table->unsignedSmallInteger('year');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['present', 'absent', 'half_day'])->default('present');
            $table->enum('shift', ['Morning', 'Evening', 'Night'])->nullable();
            $table->foreignId('leave_id')->nullable()->constrained('leaves')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['attendance_date', 'user_id']);
            $table->index(['year', 'month']);
            $table->index(['status', 'shift']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
