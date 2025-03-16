<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Http\Request;

class ClassroomController extends Controller
{
    public function index()
    {
        return Classroom::with(['teachers', 'courses.teachers'])
            ->paginate(10);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'academic_year' => 'required|string',
            'teacher_ids' => 'required|array|exists:users,id,role,teacher'
        ]);

        $classroom = Classroom::create($validated);
        $classroom->teachers()->sync($validated['teacher_ids']);

        return response()->json($classroom->load('teachers'), 201);
    }

    public function show(Classroom $classroom)
    {
        return $classroom->load(['teachers', 'courses.assignments.questions']);
    }

    public function addTeachers(Request $request, Classroom $classroom)
    {
        $validated = $request->validate([
            'teacher_ids' => 'required|array|exists:users,id,role,teacher'
        ]);

        $classroom->teachers()->syncWithoutDetaching($validated['teacher_ids']);
        return response()->json($classroom->fresh()->load('teachers'));
    }

    public function removeTeachers(Request $request, Classroom $classroom)
    {
        $validated = $request->validate([
            'teacher_ids' => 'required|array|exists:users,id,role,teacher'
        ]);

        $classroom->teachers()->detach($validated['teacher_ids']);
        return response()->json($classroom->fresh()->load('teachers'));
    }

    public function update(Request $request, Classroom $classroom)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'academic_year' => 'sometimes|string',
            'teacher_id' => 'sometimes|exists:users,id,role,teacher'
        ]);

        $classroom->update($validated);
        return response()->json($classroom->fresh());
    }

    public function destroy(Classroom $classroom)
    {
        $classroom->delete();
        return response()->json(null, 204);
    }
}
