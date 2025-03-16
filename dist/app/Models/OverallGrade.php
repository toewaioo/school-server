<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OverallGrade extends Model
{
    //

    use HasFactory;

    protected $fillable = [
        'student_id',
        'subject_id',
        'grade',
        'letter_grade',
        'remarks'
    ];
    /**
     * An overall grade belongs to a student.
     */
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * An overall grade belongs to a course.
     */
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    // public function student()
    // {
    //     return $this->belongsTo(User::class, 'student_id');
    // }

    // public function course()
    // {
    //     return $this->belongsTo(Course::class);
    // }
}
