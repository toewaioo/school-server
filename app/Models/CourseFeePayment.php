<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseFeePayment extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'course_id',
        'student_id',
        'amount',
        'payment_date',
        'payment_method',
        'transaction_id',
        'remarks',
    ];

    /**
     * A fee payment belongs to a course.
     */
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /**
     * A fee payment belongs to a student.
     */
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
