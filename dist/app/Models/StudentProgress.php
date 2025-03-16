<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProgress extends Model
{
    use HasFactory;
    protected $fillable = [
        'student_id',
        'subject_id',
        'assignment_id',
        'progress_percentage',
        'status'
    ];

    /**
     * The progress record belongs to a student.
     */
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Optionally, the progress record belongs to a course.
     */
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /**
     * Optionally, the progress record belongs to an assignment.
     */
    public function assignment()
    {
        return $this->belongsTo(Assignment::class, 'assignment_id');
    }

    // public function student()
    // {
    //     return $this->belongsTo(User::class, 'student_id');
    // }

    // public function course()
    // {
    //     return $this->belongsTo(Course::class);
    // }

    // public function assignment()
    // {
    //     return $this->belongsTo(Assignment::class);
    // }
}
