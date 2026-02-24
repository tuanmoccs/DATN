<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('ai_competency_reports', function (Blueprint $table) {
      $table->id();
      $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
      $table->foreignId('class_id')->nullable()->constrained('classes')->onDelete('set null');
      $table->enum('report_type', ['lesson', 'class', 'overall'])->default('class');
      $table->foreignId('lesson_id')->nullable()->constrained('lessons')->onDelete('set null');
      $table->decimal('average_score', 5, 2)->nullable();
      $table->integer('total_quizzes_taken')->default(0);
      $table->integer('total_assignments_completed')->default(0);
      $table->longText('strengths')->nullable(); // JSON from AI
      $table->longText('weaknesses')->nullable(); // JSON from AI
      $table->longText('recommendations')->nullable(); // JSON from AI
      $table->longText('overall_summary')->nullable(); // AI generated text
      $table->text('ai_prompt_used')->nullable();
      $table->text('ai_model_used')->nullable();
      $table->foreignId('generated_by')->nullable()->constrained('users')->onDelete('set null');
      $table->timestamp('generated_at');
      $table->timestamps();

      $table->index('student_id');
      $table->index('class_id');
      $table->index('report_type');
      $table->index('generated_at');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('ai_competency_reports');
  }
};
