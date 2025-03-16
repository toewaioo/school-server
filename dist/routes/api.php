<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//use App\Http\Controllers\\TeacherController;

// Route::prefix('v1')->group(function () {
// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);


/*
 * =======================
 * ADMIN  Routes
 * =======================
 */
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    // Dashboard and analytics
    Route::get('dashboard', [AdminController::class, 'dashboard']);

    // User management
    Route::get('users/{role}', [AdminController::class, 'listUsersByRole']);
    Route::get('users', [AdminController::class, 'listUsers']);
    Route::post('users', [AdminController::class, 'createUser']);
    Route::put('users/{id}', [AdminController::class, 'updateUser']);
    Route::delete('users/{id}', [AdminController::class, 'deleteUser']);

    // Course management
    Route::get('courses', [AdminController::class, 'listCourses']);
    Route::post('courses', [AdminController::class, 'createCourse']);
    Route::put('courses/{id}', [AdminController::class, 'updateCourse']);
    Route::delete('courses/{id}', [AdminController::class, 'deleteCourse']);

    // Assignment analytics
    Route::get('assignments', [AdminController::class, 'listAssignments']);
    Route::get('assignments/{assignmentId}/analytics', [AdminController::class, 'assignmentAnalytics']);

    // Teacher salary analytics
    Route::get('teacher-salaries', [AdminController::class, 'listTeacherSalaries']);
    Route::get('teacher-salaries/analytics', [AdminController::class, 'teacherSalaryAnalytics']);

    // Course fee payment analytics
    Route::get('course-fee-payments', [AdminController::class, 'listCourseFeePayments']);
    Route::get('course-fee-payments/analytics', [AdminController::class, 'courseFeePaymentAnalytics']);

    // Student progress and overall grades analytics
    Route::get('student-progress/analytics', [AdminController::class, 'studentProgressAnalytics']);
    Route::get('overall-grades/analytics', [AdminController::class, 'overallGradesAnalytics']);
});

/*
 * =======================
 * TEACHER  Routes
 * =======================
 */
Route::middleware(['auth:sanctum', 'role:teacher'])->prefix('teacher')->group(function () {
    // Assignment management
    Route::get('assignments', [TeacherController::class, 'listAssignments']);
    Route::post('assignments', [TeacherController::class, 'createAssignment']);
    Route::put('assignments/{id}', [TeacherController::class, 'updateAssignment']);
    Route::delete('assignments/{id}', [TeacherController::class, 'deleteAssignment']);

    // Question management for assignments
    Route::post('assignments/{assignmentId}/questions', [TeacherController::class, 'addQuestion']);

    // Assignment analytics and grade management
    Route::get('assignments/{assignmentId}/analytics', [TeacherController::class, 'assignmentAnalytics']);
    Route::get('assignments/{assignmentId}/export-grades', [TeacherController::class, 'exportGrades']);
    Route::put('assignments/{assignmentId}/recalculate', [TeacherController::class, 'recalculateGrades']);

    // Teacher-specific data
    Route::get('my-salaries', [TeacherController::class, 'mySalaryRecords']);
    Route::get('my-progress', [TeacherController::class, 'myAssignmentsProgress']);
});

/*
 * =======================
 * STUDENT  Routes
 * =======================
 */
Route::middleware(['auth:sanctum', 'role:student'])->prefix('student')->group(function () {
    // Courses and assignments
    Route::get('courses', [StudentController::class, 'listCourses']);
    Route::get('courses/{courseId}/assignments', [StudentController::class, 'listCourseAssignments']);
    Route::get('assignments/{assignmentId}', [StudentController::class, 'showAssignment']);

    // Answer submission
    Route::post('assignments/{assignmentId}/questions/{questionId}/submit', [StudentController::class, 'submitAnswer']);
    Route::post('assignments/{assignmentId}/submit-all', [StudentController::class, 'submitAllAnswers']);

    // Progress, feedback, and overall grade
    Route::get('progress', [StudentController::class, 'viewProgress']);
    Route::get('assignments/{assignmentId}/feedback', [StudentController::class, 'assignmentFeedback']);
    Route::get('progress/chart', [StudentController::class, 'progressChart']);
    Route::get('overall-grade', [StudentController::class, 'viewOverallGrade']);
    Route::get('profile', [StudentController::class, 'profile']);

    // Payment endpoint
    Route::post('courses/{courseId}/pay-fee', [StudentController::class, 'payCourseFee']);

    // New endpoints: Get all classrooms with courses, and enrolled classrooms with courses.
    Route::get('classrooms', [StudentController::class, 'getAllClassrooms']);
    Route::get('enrolled-classrooms', [StudentController::class, 'getEnrolledClassrooms']);
});
