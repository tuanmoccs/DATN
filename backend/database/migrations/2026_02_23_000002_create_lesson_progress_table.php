<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('lesson_progress', function (Blueprint $table) {
      $table->id();
      $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
      $table->foreignId('lesson_id')->constrained('lessons')->onDelete('cascade');
      $table->enum('status', ['not_started', 'in_progress', 'completed'])->default('not_started');
      $table->integer('slides_viewed')->default(0);
      $table->integer('total_slides')->default(0);
      $table->integer('time_spent')->default(0); // seconds
      $table->timestamp('started_at')->nullable();
      $table->timestamp('completed_at')->nullable();
      $table->timestamps();

      $table->unique(['student_id', 'lesson_id']);

      $table->index('student_id');
      $table->index('lesson_id');
      $table->index('status');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('lesson_progress');
  }
};
