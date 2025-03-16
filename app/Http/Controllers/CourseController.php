<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index()
    {
        return Course::with(['teachers', 'classroom', 'assignments'])
            ->paginate(10);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'classroom_id' => 'required|exists:classrooms,id',
            'teacher_ids' => 'required|array|exists:users,id,role,teacher'
        ]);

        $course = Course::create($validated);
        $course->teachers()->sync($validated['teacher_ids']);

        return response()->json($course->load('teachers'), 201);
    }

    public function addTeachers(Request $request, Course $course)
    {
        $validated = $request->validate([
            'teacher_ids' => 'required|array|exists:users,id,role,teacher'
        ]);

        $course->teachers()->syncWithoutDetaching($validated['teacher_ids']);
        return response()->json($course->fresh()->load('teachers'));
    }

    public function removeTeachers(Request $request, Course $course)
    {
        $validated = $request->validate([
            'teacher_ids' => 'required|array|exists:users,id,role,teacher'
        ]);

        $course->teachers()->detach($validated['teacher_ids']);
        return response()->json($course->fresh()->load('teachers'));
    }
    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'classroom_id' => 'sometimes|exists:classrooms,id'
        ]);

        $course->update($validated);
        return response()->json($course->fresh());
    }

    public function destroy(Course $course)
    {
        $course->delete();
        return response()->json(null, 204);
    }
}
