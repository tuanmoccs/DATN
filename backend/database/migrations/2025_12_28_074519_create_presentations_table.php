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
        Schema::create('presentations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->unique()->constrained('lessons');
            $table->integer('current_version')->default(1);
            $table->enum('status', ['draft', 'pending_review', 'published', 'archived'])->default('draft');
            $table->enum('generated_by', ['ai', 'teacher'])->default('teacher');
            $table->text('ai_prompt')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indices
            $table->index('status');
            $table->index('generated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presentations');
    }
};
