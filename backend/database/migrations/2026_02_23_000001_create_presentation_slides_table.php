<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('presentation_slides', function (Blueprint $table) {
      $table->id();
      $table->foreignId('presentation_id')->constrained('presentations')->onDelete('cascade');
      $table->integer('order');
      $table->string('title')->nullable();
      $table->longText('content');
      $table->text('notes')->nullable();
      $table->string('image_url', 500)->nullable();
      $table->enum('layout', ['title', 'content', 'two_column', 'image', 'bullet_points'])->default('content');
      $table->timestamps();
      $table->softDeletes();

      $table->index(['presentation_id', 'order']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('presentation_slides');
  }
};
