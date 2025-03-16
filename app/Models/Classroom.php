<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    //
    protected $fillable = ['name', 'academic_year'];

    /**
     * A classroom can offer many courses.
     */
    public function courses()
    {
        return $this->hasMany(Course::class, 'classroom_id');
    }

    /**
     * Optionally, you can retrieve the teachers in this classroom
     * through the courses.
     */
    public function teachers()
    {
        return $this->hasManyThrough(
            User::class,      // Final model (teacher)
            Course::class,    // Intermediate model
            'classroom_id',   // Foreign key on courses table...
            'id',             // Foreign key on users table (if using teacher_id, we override below)
            'id',             // Local key on classrooms table
            'teacher_id'      // Local key on courses table
        );
    }

    // // Optionally, a classroom has many students (via pivot table).
    // public function students()
    // {
    //     return $this->belongsToMany(User::class, 'classroom_student_course', 'classroom_id', 'student_id');
    // }

    // // Optionally, a classroom has many teachers.
    // public function teachers()
    // {
    //     return $this->belongsToMany(User::class, 'classroom_teacher_course', 'classroom_id', 'teacher_id');
    // }
}
