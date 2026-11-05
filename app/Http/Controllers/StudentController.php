<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    // GET /alumnos - listado de estudiantes para el rol profesor (y admin)
    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasAnyRole(['profesor', 'admin']), 403);

        $students = User::role('student')
            ->withCount('assignments')
            ->search($request->query('search'))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('professor.students', compact('students'));
    }
}
