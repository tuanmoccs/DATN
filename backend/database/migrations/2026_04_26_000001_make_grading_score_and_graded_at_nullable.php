<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grading', function (Blueprint $table) {
            $table->decimal('score', 5, 2)->nullable()->change();
            $table->dateTime('graded_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('grading', function (Blueprint $table) {
            $table->decimal('score', 5, 2)->nullable(false)->change();
            $table->dateTime('graded_at')->nullable(false)->change();
        });
    }
};
