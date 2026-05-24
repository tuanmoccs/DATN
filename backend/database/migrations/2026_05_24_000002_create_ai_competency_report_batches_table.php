<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('ai_competency_report_batches', function (Blueprint $table) {
      $table->id();
      $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
      $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
      $table->enum('status', ['queued', 'processing', 'completed', 'failed'])->default('queued');
      $table->unsignedInteger('total_students')->default(0);
      $table->unsignedInteger('processed')->default(0);
      $table->unsignedInteger('generated')->default(0);
      $table->unsignedInteger('skipped')->default(0);
      $table->unsignedInteger('failed')->default(0);
      $table->longText('results')->nullable();
      $table->text('error_message')->nullable();
      $table->timestamp('started_at')->nullable();
      $table->timestamp('finished_at')->nullable();
      $table->timestamps();

      $table->index(['class_id', 'teacher_id']);
      $table->index('status');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('ai_competency_report_batches');
  }
};
