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
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('lessons');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('quiz_type', ['online', 'offline'])->default('online');
            $table->boolean('auto_generated')->default(false);
            $table->text('ai_prompt')->nullable();
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->integer('time_limit')->nullable(); // minutes
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('shuffle_options')->default(false);
            $table->boolean('show_answers_after')->default(false);
            $table->integer('max_attempts')->nullable();
            $table->enum('status', ['draft', 'published', 'closed', 'archived'])->default('draft');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            // Indices
            $table->index('lesson_id');
            $table->index('quiz_type');
            $table->index('status');
            $table->index(['start_time', 'end_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
