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
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quizzes');
            $table->enum('question_type', ['multiple_choice', 'short_answer', 'essay']);
            $table->longText('content');
            $table->text('explanation')->nullable();
            $table->integer('order');
            $table->integer('points')->default(1);
            $table->timestamps();
            $table->softDeletes();

            // Indices
            $table->index(['quiz_id', 'order']);
            $table->index('question_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_questions');
    }
};
