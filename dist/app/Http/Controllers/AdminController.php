<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\CourseFeePayment;
use App\Models\OverallGrade;
use App\Models\StudentProgress;
use App\Models\TeacherSalary;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Display overall dashboard analytics.
     */
    public function dashboard(Request $request)
    {
        // Overall counts and averages
        $totalUsers       = User::count();
        $totalTeachers    = User::where('role', 'teacher')->count();
        $totalStudents    = User::where('role', 'student')->count();
        $totalAdmins      = User::where('role', 'admin')->count();
        $totalCourses     = Course::count();
        $totalAssignments = Assignment::count();
        $avgGrade         = OverallGrade::avg('grade');
        $avgProgress      = StudentProgress::avg('progress_percentage');
        $totalPaidSalaries = TeacherSalary::where('status', 'paid')->sum('salary_amount');
        $totalCourseFees  = CourseFeePayment::sum('amount');

        // Teacher weekly analytics (using pay_date)
        $teacherWeekly = TeacherSalary::selectRaw("
                            YEAR(pay_date) as year, 
                            WEEK(pay_date, 1) as week, 
                            SUM(salary_amount) as total_paid, 
                            COUNT(*) as payment_count
                        ")
            ->groupBy('year', 'week')
            ->orderBy('year', 'asc')
            ->orderBy('week', 'asc')
            ->get();

        // Teacher monthly analytics
        $teacherMonthly = TeacherSalary::selectRaw("
                            YEAR(pay_date) as year, 
                            MONTH(pay_date) as month, 
                            SUM(salary_amount) as total_paid, 
                            COUNT(*) as payment_count
                        ")
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();
        //Student course fee(using payment_date)
        $studentFeeWeekly = CourseFeePayment::selectRaw("
                            YEAR(payment_date) as year, 
                            WEEK(payment_date,1) as week, 
                            SUM(amount) as total_paid, 
                            COUNT(*) as payment_count
                        ")
            ->groupBy('year', 'week')
            ->orderBy('year', 'asc')
            ->orderBy('week', 'asc')
            ->get();

        // student fee monthly analytics
        $studentFeeMonthly = CourseFeePayment::selectRaw("
                            YEAR(payment_date) as year, 
                            MONTH(payment_date) as month, 
                            SUM(amount) as total_paid, 
                            COUNT(*) as payment_count
                        ")
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        // Student weekly analytics (based on overall grade records and created_at)
        $studentWeekly = OverallGrade::selectRaw("
                            YEAR(created_at) as year, 
                            WEEK(created_at, 1) as week, 
                            AVG(grade) as average_grade, 
                            COUNT(*) as record_count
                        ")
            ->groupBy('year', 'week')
            ->orderBy('year', 'asc')
            ->orderBy('week', 'asc')
            ->get();

        // Student monthly analytics
        $studentMonthly = OverallGrade::selectRaw("
                            YEAR(created_at) as year, 
                            MONTH(created_at) as month, 
                            AVG(grade) as average_grade, 
                            COUNT(*) as record_count
                        ")
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => [
                'overall' => [
                    'total_users'         => $totalUsers,
                    'total_teachers'      => $totalTeachers,
                    'total_students'      => $totalStudents,
                    'total_admins'        => $totalAdmins,
                    'total_courses'       => $totalCourses,
                    'total_assignments'   => $totalAssignments,
                    'average_grade'       => round($avgGrade, 2),
                    'average_progress'    => round($avgProgress, 2),
                    'total_paid_salaries' => $totalPaidSalaries,
                    'total_course_fees'   => $totalCourseFees,
                ],
                'teacher_weekly'  => $teacherWeekly,
                'teacher_monthly' => $teacherMonthly,
                'student_weekly'  => $studentWeekly,
                'student_monthly' => $studentMonthly,
                'student_fee_weekly' => $studentFeeWeekly,
                'student_fee_monthly' => $studentFeeMonthly
            ]
        ], 200);
    }

    // -------------------------------
    // User Management Endpoints
    // -------------------------------
    /**
     * List all users by role.
     */
    public function listUsersByRole($role)
    {
        $users = User::orderBy('created_at', 'desc')->where('role', $role)->get();

        return response()->json(['users' => $users]);
    }
    /**
     * List all users.
     */

    public function listUsers(Request $request)
    {
        $users = User::orderBy('created_at', 'desc')->paginate(15);
        return response()->json([
            'success' => true,
            'data'    => $users,
        ], 200);
    }

    /**
     * Create a new user.
     */
    public function createUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role'     => 'required|in:admin,teacher,student',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['password'] = bcrypt($data['password']);

        $user = User::create($data);

        return response()->json([
            'success' => true,
            'data'    => $user,
            'message' => 'User created successfully.'
        ], 201);
    }

    /**
     * Update an existing user.
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'     => 'sometimes|required|string|max:255',
            'email'    => 'sometimes|required|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|nullable|string|min:6',
            'role'     => 'sometimes|required|in:admin,teacher,student',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        if (isset($data['password']) && $data['password']) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'data'    => $user,
            'message' => 'User updated successfully.'
        ], 200);
    }

    /**
     * Delete a user.
     */
    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.'
        ], 200);
    }

    // -------------------------------
    // Course Management Endpoints
    // -------------------------------

    /**
     * List all courses.
     */
    public function listCourses(Request $request)
    {
        $courses = Course::with('teacher', 'classroom')->orderBy('created_at', 'desc')->paginate(15);
        return response()->json([
            'success' => true,
            'data'    => $courses,
        ], 200);
    }

    /**
     * Create a new course.
     */
    public function createCourse(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'teacher_id'   => 'required|exists:users,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'fee'          => 'required|numeric|min:0',
            'start_date'   => 'nullable|date',
            'end_date'     => 'nullable|date|after_or_equal:start_date',
            'published'    => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        $course = Course::create($validator->validated());

        return response()->json([
            'success' => true,
            'data'    => $course,
            'message' => 'Course created successfully.'
        ], 201);
    }

    /**
     * Update a course.
     */
    public function updateCourse(Request $request, $id)
    {
        $course = Course::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'teacher_id'   => 'sometimes|required|exists:users,id',
            'classroom_id' => 'sometimes|required|exists:classrooms,id',
            'title'        => 'sometimes|required|string|max:255',
            'description'  => 'nullable|string',
            'fee'          => 'sometimes|required|numeric|min:0',
            'start_date'   => 'nullable|date',
            'end_date'     => 'nullable|date|after_or_equal:start_date',
            'published'    => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        $course->update($validator->validated());

        return response()->json([
            'success' => true,
            'data'    => $course,
            'message' => 'Course updated successfully.'
        ], 200);
    }

    /**
     * Delete a course.
     */
    public function deleteCourse($id)
    {
        $course = Course::findOrFail($id);
        $course->delete();

        return response()->json([
            'success' => true,
            'message' => 'Course deleted successfully.'
        ], 200);
    }

    // -------------------------------
    // Assignment & Analytics Endpoints
    // -------------------------------

    /**
     * List all assignments.
     */
    public function listAssignments(Request $request)
    {
        $assignments = Assignment::with('course', 'teacher')
            ->orderBy('due_date', 'asc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data'    => $assignments,
        ], 200);
    }

    /**
     * Get analytics for a specific assignment.
     * (Submission count, average score, etc.)
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

    // -------------------------------
    // Financial Analytics Endpoints
    // -------------------------------

    /**
     * List teacher salary records.
     */
    public function listTeacherSalaries(Request $request)
    {
        $salaries = TeacherSalary::with('teacher')->orderBy('pay_date', 'desc')->paginate(15);
        return response()->json([
            'success' => true,
            'data'    => $salaries,
        ], 200);
    }

    /**
     * Get analytics for teacher salaries.
     */
    public function teacherSalaryAnalytics()
    {
        $totalPaid = TeacherSalary::where('status', 'paid')->sum('salary_amount');
        $avgSalary = TeacherSalary::avg('salary_amount');
        $pendingCount = TeacherSalary::where('status', 'pending')->count();

        return response()->json([
            'success' => true,
            'data'    => [
                'total_paid'       => $totalPaid,
                'average_salary'   => round($avgSalary, 2),
                'pending_payments' => $pendingCount,
            ]
        ], 200);
    }

    /**
     * List course fee payment records.
     */
    public function listCourseFeePayments(Request $request)
    {
        $payments = CourseFeePayment::with('course', 'student')->orderBy('payment_date', 'desc')->paginate(15);
        return response()->json([
            'success' => true,
            'data'    => $payments,
        ], 200);
    }

    /**
     * Get analytics for course fee payments.
     * (Total fees collected per course.)
     */
    public function courseFeePaymentAnalytics(Request $request)
    {
        $analytics = CourseFeePayment::selectRaw('course_id, SUM(amount) as total_collected')
            ->groupBy('course_id')
            ->with('course')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $analytics,
        ], 200);
    }

    // -------------------------------
    // Progress & Grades Analytics Endpoints
    // -------------------------------

    /**
     * Get student progress analytics.
     * (Average progress per course.)
     */
    public function studentProgressAnalytics(Request $request)
    {
        $analytics = StudentProgress::selectRaw('course_id, AVG(progress_percentage) as avg_progress')
            ->groupBy('course_id')
            ->with('course')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $analytics,
        ], 200);
    }

    /**
     * Get overall grades analytics.
     * (Average grade per course.)
     */
    public function overallGradesAnalytics(Request $request)
    {
        $analytics = OverallGrade::selectRaw('course_id, AVG(grade) as avg_grade')
            ->groupBy('course_id')
            ->with('course')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $analytics,
        ], 200);
    }
    // /**
    //  * Dashboard with aggregated statistics.
    //  */
    // public function dashboard(Request $request)
    // {
    //     $totalUsers        = User::count();
    //     $totalTeachers     = User::where('role', 'teacher')->count();
    //     $totalStudents     = User::where('role', 'student')->count();
    //     $totalAssignments  = Assignment::count();
    //     $avgGrade          = OverallGrade::avg('grade');
    //     $avgProgress       = StudentProgress::avg('progress_percentage');

    //     return response()->json([

    //         'total_users'       => $totalUsers,
    //         'total_teachers'    => $totalTeachers,
    //         'total_students'    => $totalStudents,
    //         'total_assignments' => $totalAssignments,
    //         'average_grade'     => round($avgGrade, 2),
    //         'average_progress'  => round($avgProgress, 2),

    //     ], 200);
    // }
    // public function getUsersByRole($role)
    // {
    //     $users = User::where('role', $role)->get();

    //     return response()->json(['users' => $users]);
    // }

    // /**
    //  * List all users with pagination.
    //  */
    // public function listUsers(Request $request)
    // {
    //     $users = User::orderBy('created_at', 'desc')->paginate(15);
    //     return response()->json([
    //         'success' => true,
    //         'data'    => $users
    //     ], 200);
    // }

    // /**
    //  * Create a new user (admin can create teacher or student accounts).
    //  */
    // public function createUser(Request $request)
    // {
    //     $data = $request->validate([
    //         'name'     => 'required|string|max:255',
    //         'email'    => 'required|email|unique:users,email',
    //         'password' => 'required|string|min:6',
    //         'role'     => 'required|in:admin,teacher,student',
    //     ]);

    //     $data['password'] = bcrypt($data['password']);
    //     $user = User::create($data);

    //     return response()->json([
    //         'success' => true,
    //         'data'    => $user,
    //         'message' => 'User created successfully.'
    //     ], 201);
    // }

    // /**
    //  * Update an existing user.
    //  */
    // public function updateUser(Request $request, $id)
    // {
    //     $user = User::findOrFail($id);

    //     $data = $request->validate([
    //         'name'     => 'sometimes|required|string|max:255',
    //         'email'    => 'sometimes|required|email|unique:users,email,' . $user->id,
    //         'password' => 'sometimes|nullable|string|min:6',
    //         'role'     => 'sometimes|required|in:admin,teacher,student',
    //     ]);

    //     if (isset($data['password']) && $data['password']) {
    //         $data['password'] = bcrypt($data['password']);
    //     } else {
    //         unset($data['password']);
    //     }

    //     $user->update($data);

    //     return response()->json([
    //         'success' => true,
    //         'data'    => $user,
    //         'message' => 'User updated successfully.'
    //     ], 200);
    // }

    // /**
    //  * Delete a user.
    //  */
    // public function deleteUser($id)
    // {
    //     $user = User::findOrFail($id);
    //     // Optionally add additional checks so that an admin cannot delete themselves.
    //     $user->delete();

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'User deleted successfully.'
    //     ], 200);
    // }

    // /**
    //  * Get assignment analytics (for all assignments).
    //  */
    // public function assignmentAnalytics(Request $request)
    // {
    //     // For example, return average grade per assignment and submission rates.
    //     $analytics = Assignment::withCount('questions')
    //         ->with(['studentAnswers' => function ($query) {
    //             $query->selectRaw('assignment_id, AVG(score) as avg_score')->groupBy('assignment_id');
    //         }])
    //         ->orderBy('due_date', 'desc')
    //         ->get()
    //         ->map(function ($assignment) {
    //             $avgScore = $assignment->studentAnswers->first()->avg_score ?? 0;
    //             return [
    //                 'assignment_id' => $assignment->id,
    //                 'title'         => $assignment->title,
    //                 'num_questions' => $assignment->questions_count,
    //                 'average_score' => round($avgScore, 2),
    //             ];
    //         });


    //     return response()->json([
    //         'data'    => $analytics,
    //     ], 200);
    // }
}
