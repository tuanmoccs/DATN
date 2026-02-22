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
        Schema::create('assignment_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('assignments');
            $table->string('file_path', 500);
            $table->integer('file_size')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->string('file_name', 255)->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Indices
            $table->index('assignment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignment_files');
    }
};
