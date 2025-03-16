<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $fillable = [
        'teacher_id',
        'classroom_id',
        'title',
        'description',
        'fee',
        'start_date',
        'end_date',
        'published',
    ];

    /**
     * A course belongs to a teacher (the instructor).
     */
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * A course belongs to a classroom.
     */
    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    /**
     * A course has many assignments.
     */
    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'course_id');
    }

    /**
     * A course enrolls many students.
     */
    public function enrolledStudents()
    {
        return $this->belongsToMany(User::class, 'course_student', 'course_id', 'student_id')
                    ->withTimestamps();
    }

    /**
     * A course fee payment records.
     */
    public function feePayments()
    {
        return $this->hasMany(CourseFeePayment::class, 'course_id');
    }
    ////////////////////////////////////////////////////////////////////


    // public function classroom()
    // {
    //     return $this->belongsTo(Classroom::class);
    // }

    // public function assignments()
    // {
    //     return $this->hasMany(Assignment::class);
    // }

    // // Many-to-many relationships (optional)
    // public function students()
    // {
    //     return $this->belongsToMany(User::class, 'classroom_student_course', 'course_id', 'student_id');
    // }

    // public function teachers()
    // {
    //     return $this->belongsToMany(User::class, 'classroom_teacher_course', 'course_id', 'teacher_id');
    // }
}
