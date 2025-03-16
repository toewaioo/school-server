<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Assignment;
use App\Models\Question;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Clear existing data (optional: be careful in production)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('course_student')->truncate();
        DB::table('questions')->truncate();
        DB::table('assignments')->truncate();
        DB::table('courses')->truncate();
        DB::table('classrooms')->truncate();
        DB::table('users')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // ------------------------
        // Create Users
        // ------------------------

        // Create one Admin
        $admin = User::create([
            'name'     => 'Admin User',
            'email'    => 'admin@example.com',
            'password' => bcrypt('password'),
            'role'     => 'admin',
        ]);

        // Create two Teachers
        $teacher1 = User::create([
            'name'     => 'Teacher One',
            'email'    => 'teacher1@example.com',
            'password' => bcrypt('password'),
            'role'     => 'teacher',
        ]);

        $teacher2 = User::create([
            'name'     => 'Teacher Two',
            'email'    => 'teacher2@example.com',
            'password' => bcrypt('password'),
            'role'     => 'teacher',
        ]);

        // Create five Students
        $students = [];
        for ($i = 1; $i <= 5; $i++) {
            $students[] = User::create([
                'name'     => 'Student ' . $i,
                'email'    => "student{$i}@example.com",
                'password' => bcrypt('password'),
                'role'     => 'student',
            ]);
        }

        // ------------------------
        // Create Classrooms
        // ------------------------
        $classroom1 = Classroom::create([
            'name' => 'Classroom A',
            'academic_year' => '2022-2024'
        ]);

        $classroom2 = Classroom::create([
            'name' => 'Classroom B',

            'academic_year' => '2022-2024'
        ]);

        // ------------------------
        // Create Courses
        // ------------------------
        // Course 1: Taught by Teacher One in Classroom A
        $course1 = Course::create([
            'teacher_id'   => $teacher1->id,
            'classroom_id' => $classroom1->id,
            'title'        => 'Biology 101',
            'description'  => 'Introduction to Biology',
            'fee'          => 300.00,
            'start_date'   => '2025-03-01',
            'end_date'     => '2025-06-01',
            'published'    => true,
        ]);

        // Course 2: Taught by Teacher Two in Classroom B
        $course2 = Course::create([
            'teacher_id'   => $teacher2->id,
            'classroom_id' => $classroom2->id,
            'title'        => 'Math 101',
            'description'  => 'Introduction to Mathematics',
            'fee'          => 250.00,
            'start_date'   => '2025-03-05',
            'end_date'     => '2025-06-05',
            'published'    => true,
        ]);

        // ------------------------
        // Enroll Students in Courses
        // ------------------------
        // Assumes the User model defines a belongsToMany "enrolledCourses" relationship.
        foreach ($students as $student) {
            $student->enrolledCourses()->attach([$course1->id, $course2->id]);
        }

        // ------------------------
        // Create Assignments
        // ------------------------
        // Assignment for Course 1
        $assignment1 = Assignment::create([
            'title'         => 'Biology Assignment 1',
            'description'   => 'Study cell structures.',
            'instructions'  => 'Answer all questions in detail.',
            'course_id'     => $course1->id,
            'teacher_id'    => $teacher1->id,
            'due_date'      => '2025-03-15 23:59:59',
            'max_points'    => 100,
            'published'     => true,
        ]);

        // Assignment for Course 2
        $assignment2 = Assignment::create([
            'title'         => 'Math Assignment 1',
            'description'   => 'Solve algebra problems.',
            'instructions'  => 'Show your work.',
            'course_id'     => $course2->id,
            'teacher_id'    => $teacher2->id,
            'due_date'      => '2025-03-20 23:59:59',
            'max_points'    => 100,
            'published'     => true,
        ]);

        // ------------------------
        // Create Questions for Assignments
        // ------------------------
        // For Assignment 1 (Biology)
        Question::create([
            'assignment_id' => $assignment1->id,
            'question_text' => 'What is the powerhouse of the cell?',
            'options'       => json_encode([
                'A' => 'Mitochondria',
                'B' => 'Nucleus',
                'C' => 'Ribosome',
                'D' => 'Chloroplast',
            ]),
            'correct_answer' => 'A',
            'points'         => 10,
        ]);

        Question::create([
            'assignment_id' => $assignment1->id,
            'question_text' => 'Which organelle is responsible for photosynthesis?',
            'options'       => json_encode([
                'A' => 'Mitochondria',
                'B' => 'Chloroplast',
                'C' => 'Nucleus',
                'D' => 'Endoplasmic Reticulum',
            ]),
            'correct_answer' => 'B',
            'points'         => 10,
        ]);

        // For Assignment 2 (Math)
        Question::create([
            'assignment_id' => $assignment2->id,
            'question_text' => 'Solve for x: 2x + 3 = 7',
            'options'       => json_encode([
                'A' => '1',
                'B' => '2',
                'C' => '3',
                'D' => '4',
            ]),
            'correct_answer' => 'B',
            'points'         => 10,
        ]);

        Question::create([
            'assignment_id' => $assignment2->id,
            'question_text' => 'Simplify: (3x^2) * (2x^3)',
            'options'       => json_encode([
                'A' => '6x^5',
                'B' => '6x^6',
                'C' => '5x^5',
                'D' => '5x^6',
            ]),
            'correct_answer' => 'A',
            'points'         => 10,
        ]);

        $this->command->info('Database seeded successfully for testing all roles.');
    }
}
