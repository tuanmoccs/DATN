<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('notifications', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
      $table->enum('type', [
        'lesson_published',
        'quiz_published',
        'quiz_graded',
        'assignment_published',
        'assignment_graded',
        'enrollment_accepted',
        'competency_report',
        'general'
      ]);
      $table->string('title');
      $table->text('content')->nullable();
      $table->string('action_url', 500)->nullable();
      $table->json('metadata')->nullable();
      $table->boolean('is_read')->default(false);
      $table->timestamp('read_at')->nullable();
      $table->timestamps();

      $table->index(['user_id', 'is_read']);
      $table->index('type');
      $table->index('created_at');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('notifications');
  }
};
