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
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes');
            $table->string('title');
            $table->text('description')->nullable();
            $table->longText('instructions')->nullable();
            $table->string('file_path', 500)->nullable();
            $table->integer('file_size')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->dateTime('due_date')->nullable();
            $table->integer('max_score')->default(100);
            $table->boolean('allow_late_submission')->default(false);
            $table->integer('late_penalty')->default(0); // percentage
            $table->enum('submission_type', ['file', 'text', 'both'])->default('both');
            $table->enum('status', ['draft', 'published', 'closed', 'archived'])->default('draft');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            // Indices
            $table->index('class_id');
            $table->index('due_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
