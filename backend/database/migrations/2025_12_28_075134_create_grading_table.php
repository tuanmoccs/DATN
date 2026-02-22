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
        Schema::create('grading', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->unique()->constrained('assignment_submissions');
            $table->foreignId('graded_by')->constrained('users');
            $table->decimal('score', 5, 2);
            $table->integer('max_score')->default(100);
            $table->decimal('percentage', 5, 2)->nullable();
            $table->longText('feedback')->nullable();
            $table->dateTime('graded_at');
            $table->timestamps();

            // Indices
            $table->index('graded_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grading');
    }
};
