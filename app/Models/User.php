<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use SoftDeletes;
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
    /**
     * Relationship: If the user is a teacher, they have many courses.
     */
    public function courses()
    {
        // For teachers: one teacher can have many courses
        return $this->hasMany(Course::class, 'teacher_id');
    }
    /**
     * Relationship: If the user is a teacher, they have many teacher salary records.
     */
    public function teacherSalaries()
    {
        return $this->hasMany(TeacherSalary::class, 'teacher_id');
    }

    /**
     * Relationship: If the user is a student, they are enrolled in many courses.
     */
    public function enrolledCourses()
    {
        // Pivot table: course_student
        return $this->belongsToMany(Course::class, 'course_student', 'student_id', 'course_id')
            ->withTimestamps();
    }
    ///////////////////


    // If the user is a teacher, they can have many assignments.
    // public function assignments()
    // {
    //     return $this->hasMany(Assignment::class, 'teacher_id');
    // }

    // // If the user is a student, they can have many student answers.
    // public function studentAnswers()
    // {
    //     return $this->hasMany(StudentAnswer::class, 'student_id');
    // }

    // // If the user is a student, they can have many overall grades.
    // public function overallGrades()
    // {
    //     return $this->hasMany(OverallGrade::class, 'student_id');
    // }

    // // Many-to-many relationship: A student belongs to many classrooms (via pivot table).
    // public function classroomsAsStudent()
    // {
    //     return $this->belongsToMany(Classroom::class, 'classroom_student_course', 'student_id', 'classroom_id');
    // }

    // // Many-to-many relationship: A teacher belongs to many classrooms (via pivot table).
    // public function classroomsAsTeacher()
    // {
    //     return $this->belongsToMany(Classroom::class, 'classroom_teacher_course', 'teacher_id', 'classroom_id');
    // }
}
