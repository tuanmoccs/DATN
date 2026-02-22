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
        Schema::create('lesson_content', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('lessons');
            $table->enum('content_type', ['text', 'pdf', 'url', 'html']);
            $table->longText('content_text')->nullable();
            $table->string('file_path', 500)->nullable();
            $table->integer('file_size')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->boolean('is_primary')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Indices
            $table->index('lesson_id');
            $table->index('content_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_content');
    }
};
