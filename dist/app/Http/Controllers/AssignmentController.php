<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssignmentController extends Controller
{
    public function index(Course $course)
    {
        return $course->assignments()
            ->with('questions')
            ->paginate(10);
    }

    public function store(Request $request, Course $course)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'due_date' => 'required|date',
            'total_points' => 'required|integer',
            'questions' => 'required|array',
            'questions.*.question_text' => 'required|string',
            'questions.*.type' => 'required|in:multiple_choice,true_false,short_answer',
            'questions.*.choices' => 'nullable|array',
            'questions.*.correct_answer' => 'required|string',
            'questions.*.points' => 'required|integer'
        ]);

        $assignment = DB::transaction(function () use ($course, $validated) {
            $assignment = $course->assignments()->create($validated);
            foreach ($validated['questions'] as $question) {
                $assignment->questions()->create($question);
            }
            return $assignment->load('questions');
        });

        return response()->json($assignment, 201);
    }

    public function show(Assignment $assignment)
    {
        return $assignment->load(['course.classroom', 'questions', 'submissions.student']);
    }

    public function update(Request $request, Assignment $assignment)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'due_date' => 'sometimes|date',
            'total_points' => 'sometimes|integer'
        ]);

        $assignment->update($validated);
        return response()->json($assignment->fresh());
    }

    public function destroy(Assignment $assignment)
    {
        $assignment->delete();
        return response()->json(null, 204);
    }
}
