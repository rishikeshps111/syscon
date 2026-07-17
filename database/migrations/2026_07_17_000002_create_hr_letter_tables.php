<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_letter_templates', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 30);
            $table->string('language', 50);
            $table->string('template_name');
            $table->string('subject');
            $table->longText('content');
            $table->string('header_logo')->nullable();
            $table->text('header_address')->nullable();
            $table->text('footer_content')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['entity_type', 'language', 'is_active']);
        });

        Schema::create('generated_hr_letters', function (Blueprint $table) {
            $table->id();
            $table->string('letter_number')->unique();
            $table->foreignId('template_id')->nullable()->constrained('hr_letter_templates')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('entity_type', 30);
            $table->string('language', 50);
            $table->string('subject');
            $table->longText('content');
            $table->string('header_logo')->nullable();
            $table->text('header_address')->nullable();
            $table->text('footer_content')->nullable();
            $table->json('additional_data')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at');
            $table->timestamps();
            $table->index(['user_id', 'entity_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_hr_letters');
        Schema::dropIfExists('hr_letter_templates');
    }
};
