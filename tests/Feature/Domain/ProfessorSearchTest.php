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

    $this->profesor = User::factory()->create();
    $this->profesor->assignRole('profesor');
});

test('el panel de alumnos no muestra la columna institucion', function () {
    $student = User::factory()->create([
        'name' => 'Camila Rojas',
        'institution_name' => 'Escuela Técnica 3',
        'career_year' => 2,
    ]);
    $student->assignRole('student');

    $this->actingAs($this->profesor)->get('/alumnos')
        ->assertOk()
        ->assertSee('Camila Rojas')
        ->assertSee('Año de carrera')
        ->assertSee('2° año')
        ->assertDontSee('Institución')
        ->assertDontSee('Escuela Técnica 3');
});

test('el profesor busca alumnos por nombre', function () {
    $camila = User::factory()->create(['name' => 'Camila Rojas']);
    $camila->assignRole('student');
    $juan = User::factory()->create(['name' => 'Juan Pérez']);
    $juan->assignRole('student');

    $this->actingAs($this->profesor)
        ->get('/alumnos?search=Camila')
        ->assertOk()
        ->assertSee('Camila Rojas')
        ->assertDontSee('Juan Pérez')
        ->assertSee('Resultados para «Camila»');
});

test('el profesor busca alumnos por email o institución', function () {
    $student = User::factory()->create([
        'name' => 'Nicolás Herrera',
        'email' => 'nico@escuela.edu.ar',
        'institution_name' => 'Escuela Técnica 3',
    ]);
    $student->assignRole('student');

    $this->actingAs($this->profesor)
        ->get('/alumnos?search=escuela.edu')
        ->assertOk()
        ->assertSee('Nicolás Herrera');

    $this->actingAs($this->profesor)
        ->get('/alumnos?search=Técnica')
        ->assertOk()
        ->assertSee('Nicolás Herrera');
});

test('el buscador de alumnos muestra un mensaje sin resultados', function () {
    $student = User::factory()->create(['name' => 'Camila Rojas']);
    $student->assignRole('student');

    $this->actingAs($this->profesor)
        ->get('/alumnos?search=inexistente')
        ->assertOk()
        ->assertSee('No se encontraron alumnos con ese criterio.');
});

test('el profesor busca postulaciones por nombre del alumno', function () {
    $camila = User::factory()->create(['name' => 'Camila Rojas']);
    $camila->assignRole('student');
    $juan = User::factory()->create(['name' => 'Juan Pérez']);
    $juan->assignRole('student');

    $idea = ProjectIdea::factory()->create(['title' => 'Sistema de biblioteca']);

    Assignment::factory()->create(['project_idea_id' => $idea->id, 'student_id' => $camila->id]);
    Assignment::factory()->create(['project_idea_id' => $idea->id, 'student_id' => $juan->id]);

    $this->actingAs($this->profesor)
        ->get('/assignments?search=Camila')
        ->assertOk()
        ->assertSee('Camila Rojas')
        ->assertDontSee('Juan Pérez')
        ->assertSee('Resultados para «Camila»');
});

test('el profesor busca postulaciones por título del proyecto', function () {
    $student = User::factory()->create(['name' => 'Camila Rojas']);
    $student->assignRole('student');

    $biblioteca = ProjectIdea::factory()->create(['title' => 'Sistema de biblioteca']);
    $reciclaje = ProjectIdea::factory()->create(['title' => 'App de reciclaje']);

    Assignment::factory()->create(['project_idea_id' => $biblioteca->id, 'student_id' => $student->id]);
    Assignment::factory()->create(['project_idea_id' => $reciclaje->id, 'student_id' => $student->id]);

    $this->actingAs($this->profesor)
        ->get('/assignments?search=biblioteca')
        ->assertOk()
        ->assertSee('Sistema de biblioteca')
        ->assertDontSee('App de reciclaje');
});

test('el buscador de postulaciones muestra un mensaje sin resultados', function () {
    $this->actingAs($this->profesor)
        ->get('/assignments?search=inexistente')
        ->assertOk()
        ->assertSee('No se encontraron postulaciones con ese criterio.');
});

test('el buscador de postulaciones conserva el filtro al paginar', function () {
    $student = User::factory()->create(['name' => 'Camila Rojas']);
    $student->assignRole('student');
    $idea = ProjectIdea::factory()->create(['title' => 'Sistema de biblioteca']);

    Assignment::factory()->count(11)->create([
        'project_idea_id' => $idea->id,
        'student_id' => $student->id,
    ]);

    $this->actingAs($this->profesor)
        ->get('/assignments?search=biblioteca')
        ->assertOk()
        ->assertSee('search=biblioteca', false);
});

test('el profesor ve el buscador en proyectos', function () {
    $this->actingAs($this->profesor)
        ->get('/ideas')
        ->assertOk()
        ->assertSee('name="search"', false)
        ->assertSee('Buscar por nombre, beneficiario o etiqueta…');
});
