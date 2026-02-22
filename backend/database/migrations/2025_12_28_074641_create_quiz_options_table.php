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
        Schema::create('quiz_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('quiz_questions');
            $table->longText('option_text');
            $table->boolean('is_correct')->default(false);
            $table->integer('order');
            $table->text('explanation')->nullable();
            $table->timestamps();

            // Indices
            $table->index(['question_id', 'order']);
            $table->index('is_correct');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_options');
    }
};
