<?php

use App\Models\Assignment;
use App\Models\ProjectIdea;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $permissions = [
        'ViewAny:ProjectIdea', 'View:ProjectIdea',
        'ViewAny:Assignment', 'View:Assignment', 'Update:Assignment',
    ];

    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => 'profesor', 'guard_name' => 'web'])
        ->syncPermissions($permissions);

    Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web'])
        ->syncPermissions(['ViewAny:ProjectIdea', 'View:ProjectIdea', 'ViewAny:Assignment', 'View:Assignment']);

    $this->profesor = User::factory()->create(['name' => 'Ricardo Soles']);
    $this->profesor->assignRole('profesor');
});

test('el profesor ve la barra lateral con sus secciones y sin la barra superior', function () {
    $this->actingAs($this->profesor)->get('/dashboard')
        ->assertOk()
        ->assertSee('Vista Profesor')
        ->assertSee('Ricardo Soles')
        ->assertSee('Panel')
        ->assertSee('Alumnos')
        ->assertSee('Postulaciones')
        ->assertSee('Proyectos')
        ->assertDontSee('<header', false)
        ->assertDontSee('<span class="text-xs bg-slate-100 border border-slate-200 rounded px-2 py-1">Profesor</span>', false);
});

test('el dashboard del profesor muestra el resumen general', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    $idea = ProjectIdea::factory()->create();
    Assignment::factory()->create(['project_idea_id' => $idea->id, 'student_id' => $student->id]);

    $this->actingAs($this->profesor)->get('/dashboard')
        ->assertOk()
        ->assertSee('Hola, Ricardo Soles')
        ->assertSee('Ideas disponibles');
});

test('el profesor puede ver el listado de alumnos', function () {
    $student = User::factory()->create([
        'name' => 'Alumno Uno',
        'career_year' => 2,
    ]);
    $student->assignRole('student');

    $this->actingAs($this->profesor)->get('/alumnos')
        ->assertOk()
        ->assertSee('Alumnos')
        ->assertSee('Alumno Uno')
        ->assertSee('Año de carrera')
        ->assertSee('2° año')
        ->assertDontSee('Institución');
});

test('un alumno no puede ver el listado de alumnos', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    $this->actingAs($student)->get('/alumnos')->assertForbidden();
});

test('el alumno sigue usando la navegacion superior', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    $this->actingAs($student)->get('/dashboard')
        ->assertOk()
        ->assertDontSee('Vista Profesor')
        ->assertSee('<span class="text-xs bg-slate-100 border border-slate-200 rounded px-2 py-1">Estudiante</span>', false);
});
