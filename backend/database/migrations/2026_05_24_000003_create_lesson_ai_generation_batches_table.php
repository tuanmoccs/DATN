<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('lesson_ai_generation_batches', function (Blueprint $table) {
      $table->id();
      $table->foreignId('lesson_id')->constrained('lessons')->onDelete('cascade');
      $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
      $table->enum('type', ['slides', 'quiz', 'all']);
      $table->enum('status', ['queued', 'processing', 'completed', 'failed'])->default('queued');
      $table->unsignedTinyInteger('progress')->default(0);
      $table->unsignedInteger('slide_count')->default(10);
      $table->unsignedInteger('question_count')->default(5);
      $table->longText('options')->nullable();
      $table->longText('result')->nullable();
      $table->text('message')->nullable();
      $table->text('error_message')->nullable();
      $table->timestamp('started_at')->nullable();
      $table->timestamp('finished_at')->nullable();
      $table->timestamps();

      $table->index(['lesson_id', 'teacher_id']);
      $table->index(['teacher_id', 'status']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('lesson_ai_generation_batches');
  }
};
