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
        Schema::create('course_student', function (Blueprint $table) {
            $table->unsignedBigInteger('student_id');

            $table->unsignedBigInteger('course_id');
            $table->timestamps();

            // Composite primary key
            $table->primary(['student_id', 'course_id']);

            $table->foreign('student_id')
                ->references('id')->on('users')
                ->onDelete('cascade');


            $table->foreign('course_id')
                ->references('id')->on('courses')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_student');
    }
};
