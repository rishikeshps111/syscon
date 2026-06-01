<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depot_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('assignable_type');
            $table->unsignedBigInteger('assignable_id');
            $table->foreignId('depot_id')->constrained('depots')->cascadeOnDelete();
            $table->date('from_date');
            $table->date('to_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['assignable_type', 'assignable_id', 'from_date', 'to_date'], 'depot_assignments_assignable_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depot_assignments');
    }
};
