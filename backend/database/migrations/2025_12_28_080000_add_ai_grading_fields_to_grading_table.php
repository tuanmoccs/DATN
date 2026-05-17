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
    Schema::table('grading', function (Blueprint $table) {
      $table->decimal('ai_suggested_score', 5, 2)->nullable()->after('score');
      $table->longText('ai_feedback')->nullable()->after('feedback');
      $table->enum('ai_status', ['pending', 'processing', 'completed', 'failed'])->default('pending')->after('ai_feedback');
      $table->timestamp('ai_graded_at')->nullable()->after('ai_status');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('grading', function (Blueprint $table) {
      $table->dropColumn(['ai_suggested_score', 'ai_feedback', 'ai_status', 'ai_graded_at']);
    });
  }
};
