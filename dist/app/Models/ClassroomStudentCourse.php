<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassroomStudentCourse extends Model
{
    //
    protected $table = 'classroom_student_course';

    protected $fillable = [
        'student_id',
        'classroom_id',
        'course_id'
    ];
}
