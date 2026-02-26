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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained();
            $table->foreignId('course_id')->constrained();
            $table->foreignId('semester_id')->constrained();
            $table->foreignId('exam_type_id')->constrained();
            $table->timestamps();

            $table->unique(['department_id', 'course_id', 'semester_id', 'exam_type_id'], 'questions_unique_combination');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
