<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseFeePayment;
use App\Models\OverallGrade;
use App\Models\StudentAnswer;
use App\Models\StudentProgress;
use App\Services\GradeCalculatorService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    /**
     * List all published courses.
     */
    public function listCourses(Request $request)
    {
        $courses = Course::with('teacher', 'classroom')
            ->where('published', true)
            ->orderBy('start_date', 'asc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data'    => $courses,
        ], 200);
    }

    /**
     * List assignments for a specific course.
     */
    public function listCourseAssignments($courseId)
    {
        $assignments = Assignment::with('course')
            ->where('course_id', $courseId)
            ->where('published', true)
            ->orderBy('due_date', 'asc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data'    => $assignments,
        ], 200);
    }

    /**
     * Show a specific assignment with its questions.
     */
    public function showAssignment($assignmentId)
    {
        $assignment = Assignment::with('questions')
            ->where('id', $assignmentId)
            ->where('published', true)
            ->first();

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found or not published.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $assignment,
        ], 200);
    }

    /**
     * Submit a single answer for a question.
     */
    public function submitAnswer(Request $request, $assignmentId, $questionId)
    {
        $validator = Validator::make($request->all(), [
            'chosen_answer' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['student_id']    = Auth::id();
        $data['assignment_id'] = $assignmentId;
        $data['question_id']   = $questionId;
        $data['submitted_at']  = now();

        $studentAnswer = StudentAnswer::updateOrCreate(
            [
                'student_id'    => Auth::id(),
                'assignment_id' => $assignmentId,
                'question_id'   => $questionId,
            ],
            [
                'chosen_answer' => $data['chosen_answer'],
                'submitted_at'  => $data['submitted_at'],
            ]
        );

        return response()->json([
            'success' => true,
            'data'    => $studentAnswer,
            'message' => 'Answer submitted successfully.'
        ], 200);
    }

    /**
     * Bulk submit answers for an assignment.
     */
    public function submitAllAnswers(Request $request, $assignmentId)
    {
        $validator = Validator::make($request->all(), [
            'answers' => 'required|array',
            'answers.*.question_id'   => 'required|exists:questions,id',
            'answers.*.chosen_answer' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $answers = $validator->validated()['answers'];

        foreach ($answers as $answerData) {
            StudentAnswer::updateOrCreate(
                [
                    'student_id'    => Auth::id(),
                    'assignment_id' => $assignmentId,
                    'question_id'   => $answerData['question_id'],
                ],
                [
                    'chosen_answer' => $answerData['chosen_answer'],
                    'submitted_at'  => now(),
                ]
            );
        }
        // Validate and save answers (as before)...

        // After saving all answers, calculate progress and overall grade.
        $gradeService = new GradeCalculatorService();
        $progressRecord = $gradeService->updateAssignmentProgress($assignmentId, auth()->id());

        // Get course ID from assignment.
        $assignment = Assignment::findOrFail($assignmentId);
        $overallGradeRecord = $gradeService->updateOverallGrade($assignment->course_id, auth()->id());


        return response()->json([
            'success' => true,
            'message' => 'All answers submitted successfully.'
        ], 200);
    }

    /**
     * View student progress.
     */
    public function viewProgress(Request $request)
    {
        $progress = StudentProgress::where('student_id', Auth::id())
            ->orderBy('updated_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data'    => $progress,
        ], 200);
    }

    /**
     * Get detailed feedback for a specific assignment.
     */
    public function assignmentFeedback($assignmentId)
    {
        $answers = StudentAnswer::with('question')
            ->where('assignment_id', $assignmentId)
            ->where('student_id', Auth::id())
            ->get();

        $feedback = $answers->map(function ($answer) {
            return [
                'question'      => $answer->question->question_text,
                'chosen_answer' => $answer->chosen_answer,
                'score'         => $answer->score,
                'feedback'      => $answer->feedback,
                'submitted_at'  => $answer->submitted_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $feedback,
        ], 200);
    }

    /**
     * Get progress chart data for the student.
     */
    public function progressChart()
    {
        $chartData = StudentProgress::selectRaw('course_id, AVG(progress_percentage) as avg_progress')
            ->where('student_id', Auth::id())
            ->groupBy('course_id')
            ->with('course')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $chartData,
        ], 200);
    }

    /**
     * Retrieve the student's overall grade (optionally filtered by course).
     */
    public function viewOverallGrade(Request $request)
    {
        $courseId = $request->input('course_id');

        $grade = OverallGrade::where('student_id', Auth::id())
            ->when($courseId, function ($query, $courseId) {
                return $query->where('course_id', $courseId);
            })
            ->first();

        if (!$grade) {
            return response()->json([
                'success' => false,
                'message' => 'Overall grade not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $grade,
        ], 200);
    }

    /**
     * Get the authenticated student's profile data.
     */
    public function profile()
    {
        $user = Auth::user();
        $overallGrades = OverallGrade::where('student_id', $user->id)->get();

        return response()->json([
            'success' => true,
            'data'    => [
                'profile'        => $user,
                'overall_grades' => $overallGrades,
            ]
        ], 200);
    }

    /**
     * Pay course fee for an enrolled course.
     * Expected payload:
     * {
     *   "amount": 200.00,
     *   "payment_method": "credit_card",
     *   "transaction_id": "TXN123456789",
     *   "remarks": "Paid in full."
     * }
     */
    public function payCourseFee(Request $request, $courseId)
    {
        $validator = Validator::make($request->all(), [
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|max:50',
            'transaction_id' => 'required|string|unique:course_fee_payments,transaction_id',
            'remarks'        => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        $student = Auth::user();

        // Check enrollment – assuming a belongsToMany "enrolledCourses()" exists in the User model.
        if (!$student->enrolledCourses()->where('course_id', $courseId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not enrolled in this course.'
            ], 403);
        }

        $data = $validator->validated();
        $data['course_id']    = $courseId;
        $data['student_id']   = $student->id;
        $data['payment_date'] = now();

        $payment = CourseFeePayment::create($data);

        return response()->json([
            'success' => true,
            'data'    => $payment,
            'message' => 'Course fee payment successful.'
        ], 201);
    }
    /**
     * Get all classrooms with their courses.
     *
     * This endpoint returns every classroom along with all courses offered in that classroom.
     */
    public function getAllClassrooms()
    {
        $classrooms = Classroom::with('courses')->get();

        return response()->json([
            'success' => true,
            'data'    => $classrooms,
        ], 200);
    }

    /**
     * Get enrolled classrooms with the courses in which the student is enrolled.
     *
     * This endpoint fetches all courses the student is enrolled in, groups them by the classroom,
     * and returns an array where each element contains the classroom and the list of enrolled courses.
     *
     * Assumes that the User model has a belongsToMany relationship "enrolledCourses()"
     * which uses the pivot table "course_student".
     */
    public function getEnrolledClassrooms()
    {
        $student = Auth::user();

        // Get the courses that the student is enrolled in (with the classroom loaded)
        $courses = $student->enrolledCourses()->with('classroom')->get();

        // Group courses by the classroom id
        $grouped = $courses->groupBy(function ($course) {
            return $course->classroom->id;
        });

        // Map grouped data to a structure with classroom details and its courses
        $data = $grouped->map(function ($courses, $classroomId) {
            $classroom = $courses->first()->classroom;
            return [
                'classroom' => $classroom,
                'courses'   => $courses,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data'    => $data,
        ], 200);
    }

    // /**
    //  * List published assignments.
    //  *
    //  * GET /api/student/assignments
    //  */
    // public function assignmentsIndex()
    // {
    //     $assignments = Assignment::where('published', true)
    //         ->orderBy('due_date', 'asc')
    //         ->paginate(10);

    //     return response()->json([
    //         'success' => true,
    //         'data'    => $assignments,
    //     ], 200);
    // }

    // /**
    //  * Show a single published assignment with its questions.
    //  *
    //  * GET /api/student/assignments/{id}
    //  */
    // public function showAssignment($id)
    // {
    //     $assignment = Assignment::with('questions')
    //         ->where('id', $id)
    //         ->where('published', true)
    //         ->first();

    //     if (!$assignment) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Assignment not found or not published.'
    //         ], 404);
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'data'    => $assignment,
    //     ], 200);
    // }

    // /**
    //  * Submit or update an answer for a specific question.
    //  *
    //  * POST /api/student/assignments/{assignmentId}/questions/{questionId}/submit
    //  */
    // public function submitAnswer(Request $request, $assignmentId, $questionId)
    // {
    //     $data = $request->validate([
    //         'chosen_answer' => 'required|string',
    //     ]);

    //     $data['student_id']    = Auth::id();
    //     $data['assignment_id'] = $assignmentId;
    //     $data['question_id']   = $questionId;
    //     $data['submitted_at']  = now();

    //     // Update or create a student answer record for this question.
    //     $studentAnswer = StudentAnswer::updateOrCreate(
    //         [
    //             'student_id'    => Auth::id(),
    //             'assignment_id' => $assignmentId,
    //             'question_id'   => $questionId,
    //         ],
    //         [
    //             'chosen_answer' => $data['chosen_answer'],
    //             'submitted_at'  => $data['submitted_at'],
    //         ]
    //     );

    //     return response()->json([
    //         'success' => true,
    //         'data'    => $studentAnswer,
    //         'message' => 'Answer submitted successfully.'
    //     ], 200);
    // }

    // /**
    //  * Submit all answers for an assignment at once.
    //  *
    //  * POST /api/student/assignments/{assignmentId}/submitAllAnswers
    //  *
    //  * Expected JSON payload:
    //  * {
    //  *   "answers": [
    //  *     {"question_id": 15, "chosen_answer": "A"},
    //  *     {"question_id": 16, "chosen_answer": "C"},
    //  *     // ...
    //  *   ]
    //  * }
    //  */
    // public function submitAllAnswers(Request $request, $assignmentId)
    // {
    //     $data = $request->validate([
    //         'answers' => 'required|array',
    //         'answers.*.question_id' => 'required|exists:questions,id',
    //         'answers.*.chosen_answer' => 'required|string',
    //     ]);

    //     foreach ($data['answers'] as $answerData) {
    //         StudentAnswer::updateOrCreate(
    //             [
    //                 'student_id'    => Auth::id(),
    //                 'assignment_id' => $assignmentId,
    //                 'question_id'   => $answerData['question_id'],
    //             ],
    //             [
    //                 'chosen_answer' => $answerData['chosen_answer'],
    //                 'submitted_at'  => now(),
    //             ]
    //         );
    //     }

    //     // Optionally update progress here.

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'All answers submitted successfully.'
    //     ], 200);
    // }

    // /**
    //  * View the authenticated student's progress.
    //  *
    //  * GET /api/student/progress
    //  */
    // public function viewProgress()
    // {
    //     $progresses = StudentProgress::where('student_id', Auth::id())
    //         ->orderBy('updated_at', 'desc')
    //         ->paginate(10);

    //     return response()->json([
    //         'success' => true,
    //         'data'    => $progresses,
    //     ], 200);
    // }

    // /**
    //  * View the overall grade for the authenticated student.
    //  *
    //  * GET /api/student/grades?subject_id={subjectId}
    //  */
    // public function viewOverallGrade(Request $request)
    // {
    //     $subjectId = $request->input('subject_id');

    //     $grade = OverallGrade::where('student_id', Auth::id())
    //         ->when($subjectId, function ($query, $subjectId) {
    //             return $query->where('subject_id', $subjectId);
    //         })
    //         ->first();

    //     if (!$grade) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Grade not found.'
    //         ], 404);
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'data'    => $grade,
    //     ], 200);
    // }

    // /**
    //  * Get detailed progress chart data for the authenticated student.
    //  * For example, aggregate progress per subject.
    //  *
    //  * GET /api/student/progress/chart
    //  */
    // public function progressChart()
    // {
    //     $progressData = StudentProgress::where('student_id', Auth::id())
    //         ->selectRaw('course_id, AVG(progress_percentage) as avg_progress, COUNT(*) as records')
    //         ->groupBy('course_id')
    //         ->get();

    //     return response()->json([
    //         'success' => true,
    //         'data'    => $progressData,
    //     ], 200);
    // }

    // /**
    //  * Retrieve detailed feedback for a specific assignment.
    //  * This returns a list of questions with the student's answer, score, and teacher feedback.
    //  *
    //  * GET /api/student/assignments/{assignmentId}/feedback
    //  */
    // public function assignmentFeedback($assignmentId)
    // {
    //     $answers = StudentAnswer::with('question')
    //         ->where('assignment_id', $assignmentId)
    //         ->where('student_id', Auth::id())
    //         ->get();

    //     $feedback = $answers->map(function ($answer) {
    //         return [
    //             'question'      => $answer->question->question_text,
    //             'chosen_answer' => $answer->chosen_answer,
    //             'score'         => $answer->score,
    //             'feedback'      => $answer->feedback,
    //             'submitted_at'  => $answer->submitted_at,
    //         ];
    //     });

    //     return response()->json([
    //         'success' => true,
    //         'data'    => $feedback,
    //     ], 200);
    // }

    // /**
    //  * Get the authenticated student's profile information along with aggregated grade data.
    //  *
    //  * GET /api/student/profile
    //  */
    // public function profile()
    // {
    //     $user = Auth::user();
    //     $overallGrades = OverallGrade::where('student_id', $user->id)->get();

    //     return response()->json([
    //         'success' => true,
    //         'data'    => [
    //             'profile'        => $user,
    //             'overall_grades' => $overallGrades,
    //         ],
    //     ], 200);
    // }
}
