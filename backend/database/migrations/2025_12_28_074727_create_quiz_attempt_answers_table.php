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
        Schema::create('quiz_attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('quiz_attempts');
            $table->foreignId('question_id')->constrained('quiz_questions');
            $table->enum('answer_type', ['option', 'text', 'file']);
            $table->foreignId('answer_option_id')->nullable()->constrained('quiz_options');
            $table->longText('answer_text')->nullable();
            $table->string('answer_file_path', 500)->nullable();
            $table->boolean('is_correct')->nullable();
            $table->decimal('points_earned', 5, 2)->nullable();
            $table->text('teacher_feedback')->nullable();
            $table->dateTime('submitted_at');
            $table->dateTime('graded_at')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users');
            $table->timestamps();

            // Indices
            $table->index(['attempt_id', 'question_id']);
            $table->index('is_correct');
            $table->index('graded_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_attempt_answers');
    }
};
