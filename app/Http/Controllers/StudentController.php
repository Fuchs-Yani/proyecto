<?php

namespace App\Http\Controllers;

use App\Models\User;

class StudentController extends Controller
{
    // GET /alumnos - listado de estudiantes para el rol profesor (y admin)
    public function index()
    {
        abort_unless(auth()->user()->hasAnyRole(['profesor', 'admin']), 403);

        $students = User::role('student')
            ->withCount('assignments')
            ->orderBy('name')
            ->paginate(20);

        return view('professor.students', compact('students'));
    }
}
