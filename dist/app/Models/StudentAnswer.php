<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAnswer extends Model
{
    use HasFactory;
    //
    protected $fillable = [
        'student_id',
        'assignment_id',
        'question_id',
        'chosen_answer',
        'score',
        'feedback',
        'submitted_at'
    ];

    protected $dates = ['submitted_at'];
    /**
     * A student answer belongs to a student.
     */
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * A student answer belongs to an assignment.
     */
    public function assignment()
    {
        return $this->belongsTo(Assignment::class, 'assignment_id');
    }

    /**
     * A student answer belongs to a question.
     */
    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }

    // public function student()
    // {
    //     return $this->belongsTo(User::class, 'student_id');
    // }

    // public function assignment()
    // {
    //     return $this->belongsTo(Assignment::class);
    // }

    // public function question()
    // {
    //     return $this->belongsTo(Question::class);
    // }
}
