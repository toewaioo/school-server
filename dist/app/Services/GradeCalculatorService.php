<?php
// app/Services/GradeCalculatorService.php

namespace App\Services;

use App\Models\Assignment;
use App\Models\StudentAnswer;
use App\Models\StudentProgress;
use App\Models\OverallGrade;

class GradeCalculatorService
{
    /**
     * Calculate and update progress for a given assignment and student.
     */
    public function updateAssignmentProgress($assignmentId, $studentId)
    {
        $assignment = Assignment::with('questions')->findOrFail($assignmentId);
        $totalQuestions = $assignment->questions->count();
        $submittedAnswers = StudentAnswer::where('assignment_id', $assignmentId)
            ->where('student_id', $studentId)
            ->count();

        $progressPercentage = $totalQuestions > 0 ? ($submittedAnswers / $totalQuestions) * 100 : 0;

        return StudentProgress::updateOrCreate(
            ['student_id' => $studentId, 'assignment_id' => $assignmentId],
            ['progress_percentage' => $progressPercentage, 'status' => ($progressPercentage == 100) ? 'completed' : 'in_progress']
        );
    }

    /**
     * Calculate and update overall grade for a course for a student.
     */
    public function updateOverallGrade($courseId, $studentId)
    {
        $courseAssignments = Assignment::where('course_id', $courseId)->get();

        $totalScore = 0;
        $totalMaxPoints = 0;

        foreach ($courseAssignments as $assignment) {
            $score = StudentAnswer::where('assignment_id', $assignment->id)
                ->where('student_id', $studentId)
                ->sum('score');
            $totalScore += $score;
            $totalMaxPoints += $assignment->max_points;
        }

        $overallGradePercentage = $totalMaxPoints > 0 ? ($totalScore / $totalMaxPoints) * 100 : 0;

        return OverallGrade::updateOrCreate(
            ['student_id' => $studentId, 'course_id' => $courseId],
            ['grade' => $overallGradePercentage]
        );
    }
}
