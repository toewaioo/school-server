<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\OverallGrade;
use App\Models\Question;
use App\Models\StudentAnswer;
use App\Models\StudentProgress;
use App\Models\TeacherSalary;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class TeacherController extends Controller
{
    /**
     * List assignments created by the authenticated teacher.
     */
    public function listAssignments(Request $request)
    {
        $teacherId = Auth::id();
        $assignments = Assignment::with('course')
            ->where('teacher_id', $teacherId)
            ->orderBy('due_date', 'asc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data'    => $assignments,
        ], 200);
    }

    /**
     * Create a new assignment.
     */
    public function createAssignment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'instructions'  => 'nullable|string',
            'course_id'     => 'required|exists:courses,id',
            'due_date'      => 'required|date',
            'max_points'    => 'required|integer|min:1',
            'published'     => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['teacher_id'] = Auth::id();

        $assignment = Assignment::create($data);

        return response()->json([
            'success' => true,
            'data'    => $assignment,
            'message' => 'Assignment created successfully.'
        ], 201);
    }

    /**
     * Update an assignment.
     */
    public function updateAssignment(Request $request, $id)
    {
        $assignment = Assignment::where('id', $id)
            ->where('teacher_id', Auth::id())
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'instructions'  => 'nullable|string',
            'course_id'     => 'required|exists:courses,id',
            'due_date'      => 'required|date',
            'max_points'    => 'required|integer|min:1',
            'published'     => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $assignment->update($validator->validated());

        return response()->json([
            'success' => true,
            'data'    => $assignment,
            'message' => 'Assignment updated successfully.'
        ], 200);
    }

    /**
     * Delete an assignment.
     */
    public function deleteAssignment($id)
    {
        $assignment = Assignment::where('id', $id)
            ->where('teacher_id', Auth::id())
            ->firstOrFail();
        $assignment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Assignment deleted successfully.'
        ], 200);
    }

    /**
     * Add a new question to an assignment.
     */
    public function addQuestion(Request $request, $assignmentId)
    {
        // Ensure the assignment belongs to the authenticated teacher.
        $assignment = Assignment::where('id', $assignmentId)
            ->where('teacher_id', Auth::id())
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'question_text'  => 'required|string',
            // Expects JSON for options
            'options'        => 'required|json',
            'correct_answer' => 'required|string',
            'points'         => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['assignment_id'] = $assignment->id;

        $question = Question::create($data);

        return response()->json([
            'success' => true,
            'data'    => $question,
            'message' => 'Question added successfully.'
        ], 201);
    }

    /**
     * Get analytics for a specific assignment.
     */
    public function assignmentAnalytics($assignmentId)
    {
        $assignment = Assignment::with('studentAnswers')->findOrFail($assignmentId);
        $submissionCount = $assignment->studentAnswers()->count();
        $avgScore = $assignment->studentAnswers()->avg('score');

        $analytics = [
            'assignment_id'    => $assignment->id,
            'title'            => $assignment->title,
            'submission_count' => $submissionCount,
            'average_score'    => round($avgScore, 2),
        ];

        return response()->json([
            'success' => true,
            'data'    => $analytics,
        ], 200);
    }

    /**
     * Export overall grades for an assignment.
     */
    public function exportGrades($assignmentId)
    {
        $assignment = Assignment::with('overallGrades.student')
            ->where('id', $assignmentId)
            ->where('teacher_id', Auth::id())
            ->firstOrFail();

        $grades = $assignment->overallGrades->map(function ($grade) {
            return [
                'student_id'   => $grade->student_id,
                'student_name' => $grade->student->name ?? 'N/A',
                'grade'        => $grade->grade,
                'letter_grade' => $grade->letter_grade,
                'remarks'      => $grade->remarks,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $grades,
            'message' => 'Grades exported successfully.'
        ], 200);
    }

    /**
     * Recalculate overall grades for an assignment.
     */
    public function recalculateGrades($assignmentId)
    {
        $assignment = Assignment::where('id', $assignmentId)
            ->where('teacher_id', Auth::id())
            ->firstOrFail();

        // Loop over overall grade records for the course
        $assignment->overallGrades()->each(function ($grade) use ($assignment) {
            // Assuming a method exists on the Assignment model
            $newGrade = $assignment->calculateGradeForStudent($grade->student_id);
            $grade->update([
                'grade' => $newGrade,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Grades recalculated successfully.'
        ], 200);
    }

    /**
     * List teacher salary records for the authenticated teacher.
     */
    public function mySalaryRecords(Request $request)
    {
        $teacherId = Auth::id();
        $salaries = TeacherSalary::where('teacher_id', $teacherId)
            ->orderBy('pay_date', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data'    => $salaries,
        ], 200);
    }

    /**
     * Get aggregated progress for assignments taught by the teacher.
     */
    public function myAssignmentsProgress(Request $request)
    {
        $teacherId = Auth::id();
        $assignments = Assignment::with('studentProgress')
            ->where('teacher_id', $teacherId)
            ->orderBy('due_date', 'asc')
            ->get();

        $data = $assignments->map(function ($assignment) {
            return [
                'assignment_id'   => $assignment->id,
                'title'           => $assignment->title,
                'avg_progress'    => $assignment->studentProgress->avg('progress_percentage') ?? 0,
                'submission_count' => $assignment->studentAnswers()->count(),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $data,
        ], 200);
    }
    // /**
    //  * Display a listing of assignments created by the authenticated teacher.
    //  */
    // public function indexAssignments(Request $request)
    // {
    //     $teacherId = $request->user()->id;
    //     $assignments = Assignment::where('teacher_id', $teacherId)
    //         ->orderBy('due_date', 'asc')
    //         ->paginate(10);

    //     return response()->json([
    //         'success' => true,
    //         'data'    => $assignments,
    //     ], 200);
    // }

    // /**
    //  * Store a newly created assignment.
    //  */
    // public function storeAssignment(Request $request)
    // {
    //     $data = $request->validate([
    //         'title'         => 'required|string|max:255',
    //         'description'   => 'nullable|string',
    //         'instructions'  => 'nullable|string',
    //         'subject_id'    => 'required|exists:subjects,id',
    //         'due_date'      => 'required|date',
    //         'max_points'    => 'required|integer|min:1',
    //         'published'     => 'sometimes|boolean',
    //     ]);

    //     $data['teacher_id'] = Auth::id();
    //     $assignment = Assignment::create($data);

    //     return response()->json([
    //         'success' => true,
    //         'data'    => $assignment,
    //         'message' => 'Assignment created successfully.'
    //     ], 201);
    // }

    // /**
    //  * Update an existing assignment.
    //  */
    // public function updateAssignment(Request $request, $id)
    // {
    //     $assignment = Assignment::where('id', $id)
    //         ->where('teacher_id', Auth::id())
    //         ->firstOrFail();

    //     $data = $request->validate([
    //         'title'         => 'required|string|max:255',
    //         'description'   => 'nullable|string',
    //         'instructions'  => 'nullable|string',
    //         'subject_id'    => 'required|exists:subjects,id',
    //         'due_date'      => 'required|date',
    //         'max_points'    => 'required|integer|min:1',
    //         'published'     => 'sometimes|boolean',
    //     ]);

    //     $assignment->update($data);

    //     return response()->json([
    //         'success' => true,
    //         'data'    => $assignment,
    //         'message' => 'Assignment updated successfully.'
    //     ], 200);
    // }

    // /**
    //  * Add a question to an assignment.
    //  */
    // public function addQuestion(Request $request, $assignmentId)
    // {
    //     // Ensure the assignment belongs to the authenticated teacher.
    //     $assignment = Assignment::where('id', $assignmentId)
    //         ->where('teacher_id', Auth::id())
    //         ->firstOrFail();

    //     $data = $request->validate([
    //         'question_text'  => 'required|string',
    //         // We expect a JSON string for options (or modify if sending an array)
    //         'options'        => 'required|json',
    //         'correct_answer' => 'required|string',
    //         'points'         => 'required|integer|min:1',
    //     ]);

    //     $data['assignment_id'] = $assignment->id;
    //     $question = Question::create($data);

    //     return response()->json([
    //         'success' => true,
    //         'data'    => $question,
    //         'message' => 'Question added successfully.'
    //     ], 201);
    // }

    // /**
    //  * Grade a student's answer.
    //  */
    // public function gradeAnswer(Request $request, $answerId)
    // {
    //     $data = $request->validate([
    //         'score'    => 'required|integer|min:0',
    //         'feedback' => 'nullable|string',
    //     ]);

    //     $studentAnswer = StudentAnswer::findOrFail($answerId);

    //     // Verify the assignment belongs to the authenticated teacher.
    //     if ($studentAnswer->assignment->teacher_id !== Auth::id()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Unauthorized action.'
    //         ], 403);
    //     }

    //     $studentAnswer->update($data);

    //     return response()->json([
    //         'success' => true,
    //         'data'    => $studentAnswer,
    //         'message' => 'Answer graded successfully.'
    //     ], 200);
    // }

    // /**
    //  * Get student progress, optionally filtered by assignment or subject.
    //  */
    // public function viewProgress(Request $request)
    // {
    //     $assignmentId = $request->input('assignment_id');
    //     $subjectId    = $request->input('subject_id');

    //     $query = StudentProgress::query();

    //     if ($assignmentId) {
    //         $query->where('assignment_id', $assignmentId);
    //     }
    //     if ($subjectId) {
    //         $query->where('subject_id', $subjectId);
    //     }

    //     // Optionally restrict to assignments that belong to this teacher.
    //     $teacherAssignmentIds = Assignment::where('teacher_id', Auth::id())
    //         ->pluck('id')
    //         ->toArray();
    //     if ($assignmentId) {
    //         if (!in_array($assignmentId, $teacherAssignmentIds)) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Unauthorized action.'
    //             ], 403);
    //         }
    //     } else {
    //         $query->whereIn('assignment_id', $teacherAssignmentIds);
    //     }

    //     $progresses = $query->paginate(10);

    //     return response()->json([
    //         'success' => true,
    //         'data'    => $progresses,
    //     ], 200);
    // }

    // /**
    //  * View overall grades.
    //  */
    // public function viewOverallGrades(Request $request)
    // {
    //     $subjectId = $request->input('subject_id');

    //     $query = OverallGrade::query();

    //     if ($subjectId) {
    //         $query->where('subject_id', $subjectId);
    //     }

    //     $grades = $query->paginate(10);

    //     return response()->json([
    //         'success' => true,
    //         'data'    => $grades,
    //     ], 200);
    // }
    // /**
    //  * Get detailed analytics for a specific assignment.
    //  * Returns distribution of scores and progress percentages.
    //  */
    // public function assignmentAnalytics($assignmentId)
    // {
    //     // Retrieve the assignment for the authenticated teacher.
    //     $assignment = Assignment::where('id', $assignmentId)
    //         ->where('teacher_id', Auth::id())
    //         ->with('studentAnswers')
    //         ->firstOrFail();

    //     // Count the number of submissions.
    //     $submissionCount = $assignment->studentAnswers()->count();

    //     // Calculate average score based on studentAnswers.
    //     $avgScore = $assignment->studentAnswers()->avg('score');

    //     $analytics = [
    //         'assignment_id'    => $assignment->id,
    //         'title'            => $assignment->title,
    //         'submission_count' => $submissionCount,
    //         'average_score'    => round($avgScore, 2),
    //     ];

    //     return response()->json([
    //         'success' => true,
    //         'data'    => $analytics,
    //     ], 200);
    // }

    // /**
    //  * Export overall grades for an assignment.
    //  * This method returns data in a format that could be used to generate CSVs.
    //  */
    // public function exportGrades($assignmentId)
    // {
    //     $assignment = Assignment::where('id', $assignmentId)
    //         ->where('teacher_id', Auth::id())
    //         ->firstOrFail();

    //     // Get a list of unique student IDs who submitted answers for this assignment.
    //     $studentIds = $assignment->studentAnswers()
    //         ->distinct()
    //         ->pluck('student_id');

    //     $grades = collect();

    //     foreach ($studentIds as $studentId) {
    //         // Use the helper method to calculate the grade percentage.
    //         $gradeValue = $assignment->calculateGradeForStudent($studentId);
    //         $student = User::find($studentId);

    //         $grades->push([
    //             'student_id'   => $studentId,
    //             'student_name' => $student->name,
    //             'grade'        => $gradeValue,
    //         ]);
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'data'    => $grades,
    //     ], 200);
    // }


    // /**
    //  * Recalculate overall grades for an assignment.
    //  * This could iterate over all student submissions, recalc the scores, and update OverallGrade.
    //  */
    // public function recalculateGrades($assignmentId)
    // {
    //     $assignment = Assignment::where('id', $assignmentId)
    //         ->where('teacher_id', Auth::id())
    //         ->firstOrFail();

    //     $studentIds = $assignment->studentAnswers()
    //         ->distinct()
    //         ->pluck('student_id');

    //     foreach ($studentIds as $studentId) {
    //         $newGrade = $assignment->calculateGradeForStudent($studentId);
    //         // Here you might update an OverallGrade model or perform additional logic.
    //         // For this example, we simply output the calculated grade.
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Grades recalculated successfully.'
    //     ], 200);
    // }
}
