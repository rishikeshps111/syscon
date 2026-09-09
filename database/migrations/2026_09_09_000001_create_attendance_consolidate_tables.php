<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_consolidate_imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->foreignId('depot_id')->constrained()->restrictOnDelete();
            $table->string('depot_name');
            $table->string('original_filename');
            $table->string('file_path');
            $table->char('file_checksum', 64);
            $table->unsignedInteger('employee_count');
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('imported_by_name');
            $table->timestamp('imported_at');
            $table->timestamps();
            $table->unique(['year', 'month', 'depot_id', 'file_checksum'], 'attendance_consolidate_unique_file');
        });
        Schema::create('attendance_consolidate_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('attendance_consolidate_imports')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employee_ref_code', 100);
            $table->string('employee_name');
            foreach (['present_days', 'week_off_days', 'absent_days', 'total_days'] as $column) {
                $table->decimal($column, 10, 2);
            }
            $table->unsignedInteger('source_row');
            $table->json('warnings')->nullable();
            $table->timestamps();
            $table->unique(['import_id', 'employee_ref_code'], 'attendance_consolidate_unique_employee');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_consolidate_rows');
        Schema::dropIfExists('attendance_consolidate_imports');
    }
};
