<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassroomTeacherCourse extends Model
{
    //
    protected $table = 'classroom_teacher_course';

    protected $fillable = [
        'teacher_id', 
        'classroom_id', 
        'course_id'
    ];
}
