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
        Schema::create('course_fee_payments', function (Blueprint $table) {
            $table->id();
            // The course for which payment is made
            $table->unsignedBigInteger('course_id');
            // The student making the payment
            $table->unsignedBigInteger('student_id');
            // Amount paid
            $table->decimal('amount', 10, 2);
            // Payment date (default to current timestamp)
            $table->timestamp('payment_date')->useCurrent();
            // Payment method (e.g., credit_card, paypal)
            $table->string('payment_method', 50)->nullable();
            // Transaction ID from payment gateway (must be unique)
            $table->string('transaction_id')->nullable()->unique();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('course_id')
                ->references('id')->on('courses')
                ->onDelete('cascade');

            $table->foreign('student_id')
                ->references('id')->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_fee_payments');
    }
};
