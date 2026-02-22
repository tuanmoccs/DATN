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
        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('assignments');
            $table->foreignId('student_id')->constrained('users');
            $table->dateTime('submitted_at');
            $table->boolean('is_late')->nullable();
            $table->enum('status', ['submitted', 'graded', 'returned'])->default('submitted');
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint
            $table->unique(['assignment_id', 'student_id']);

            // Indices
            $table->index('student_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignment_submissions');
    }
};
