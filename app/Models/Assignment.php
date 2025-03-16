<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    protected $fillable = [
        'title',
        'description',
        'instructions',
        'subject_id',
        'teacher_id',
        'due_date',
        'max_points',
        'published'
    ];
    /**
     * An assignment belongs to a course.
     */
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /**
     * An assignment belongs to a teacher (should match the course's teacher).
     */
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * An assignment has many questions.
     */
    public function questions()
    {
        return $this->hasMany(Question::class, 'assignment_id');
    }

    /**
     * An assignment has many student answers.
     */
    public function studentAnswers()
    {
        return $this->hasMany(StudentAnswer::class, 'assignment_id');
    }

    /**
     * (Optional) You can define a method to calculate overall grade for a student.
     */
    public function calculateGradeForStudent($studentId)
    {
        // For example, sum scores from studentAnswers for this assignment,
        // or use any other business logic.
        $score = $this->studentAnswers()
            ->where('student_id', $studentId)
            ->sum('score');

        // Return the calculated grade.
        return $score;
    }
    /////////////////////////////////////////

    // public function course()
    // {
    //     return $this->belongsTo(Course::class);
    // }

    // public function teacher()
    // {
    //     return $this->belongsTo(User::class, 'teacher_id');
    // }

    // public function questions()
    // {
    //     return $this->hasMany(Question::class);
    // }

    // public function studentAnswers()
    // {
    //     return $this->hasMany(StudentAnswer::class);
    // }
    // public function overallGrades()
    // {
    //     return $this->hasMany(OverallGrade::class, 'assignment_id');
    // }


    // // (Optional) Custom method to calculate a student’s grade for this assignment.
    // /**
    //  * Calculate the overall grade (percentage) for a given student
    //  * based on the sum of scores in studentAnswers.
    //  */
    // public function calculateGradeForStudent($studentId)
    // {
    //     // Get all answers for this student on this assignment.
    //     $answers = $this->studentAnswers()->where('student_id', $studentId)->get();

    //     // Sum of scores obtained.
    //     $totalScore = $answers->sum('score');

    //     // Use max_points field from assignment.
    //     $maxPoints = $this->max_points;

    //     // Calculate grade percentage.
    //     $grade = $maxPoints > 0 ? ($totalScore / $maxPoints * 100) : 0;

    //     return round($grade);
    // }
}
