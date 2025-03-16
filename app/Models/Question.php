<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    protected $fillable = [
        'assignment_id',
        'question_text',
        'options',
        'correct_answer',
        'points'
    ];

    // Cast the 'options' column to an array.
    protected $casts = [
        'options' => 'array',
    ];

    /**
     * A question belongs to an assignment.
     */
    public function assignment()
    {
        return $this->belongsTo(Assignment::class, 'assignment_id');
    }

    /**
     * A question can have many student answers.
     */
    public function studentAnswers()
    {
        return $this->hasMany(StudentAnswer::class, 'question_id');
    }

    // public function assignment()
    // {
    //     return $this->belongsTo(Assignment::class);
    // }

    // public function studentAnswers()
    // {
    //     return $this->hasMany(StudentAnswer::class);
    // }
}
