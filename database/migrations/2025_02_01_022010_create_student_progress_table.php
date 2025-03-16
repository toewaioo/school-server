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
        Schema::create('student_progress', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            // You can track progress per subject and/or assignment:
            $table->unsignedBigInteger('course_id')->nullable();
            $table->unsignedBigInteger('assignment_id')->nullable();
            $table->unsignedTinyInteger('progress_percentage')->default(0);
            // Status: not_started, in_progress, completed
            $table->enum('status', ['not_started', 'in_progress', 'completed'])->default('not_started');
            $table->timestamps();

            $table->foreign('student_id')
                ->references('id')->on('users')
                ->onDelete('cascade');

            // Optional foreign keys:
            $table->foreign('course_id')
                ->references('id')->on('courses')
                ->onDelete('cascade');

            $table->foreign('assignment_id')
                ->references('id')->on('assignments')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_progress');
    }
};
