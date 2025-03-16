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
        Schema::create('overall_grades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('assignment_id')->nullable();
            // Overall numerical grade
            $table->unsignedInteger('grade')->nullable();
            // Optional: letter grade (A, B, etc.)
            $table->string('letter_grade', 2)->nullable();
            // Optional remarks or comments
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('student_id')
                ->references('id')->on('users')
                ->onDelete('cascade');

            $table->foreign('course_id')
                ->references('id')->on('courses')
                ->onDelete('cascade');
            // If assignment_id is provided, add its foreign key:
            $table->foreign('assignment_id')
                ->references('id')->on('assignments')
                ->onDelete('cascade');

            // To ensure a student has only one grade per subject.
            $table->unique(['student_id', 'course_id', 'assignment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overall_grades');
    }
};
