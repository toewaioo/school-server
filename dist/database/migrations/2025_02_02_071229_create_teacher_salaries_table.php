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
        Schema::create('teacher_salaries', function (Blueprint $table) {
            $table->id();
            // Teacher receiving the salary
            $table->unsignedBigInteger('teacher_id');
            $table->decimal('salary_amount', 10, 2);
            $table->date('pay_date');
            // Status: pending, paid
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('teacher_id')
                ->references('id')->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_salaries');
    }
};
