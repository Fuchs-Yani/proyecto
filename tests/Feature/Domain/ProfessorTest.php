<?php

use App\Models\Assignment;
use App\Models\ProjectIdea;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $permissions = [
        'ViewAny:ProjectIdea',
        'View:ProjectIdea',
        'ViewAny:Assignment',
        'View:Assignment',
    ];

    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => 'profesor', 'guard_name' => 'web'])
        ->syncPermissions($permissions);

    Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web'])
        ->syncPermissions($permissions);
});

test('un profesor puede ver los postulantes de una idea', function () {
    $professor = User::factory()->create();
    $professor->assignRole('profesor');

    $owner = User::factory()->create(['name' => 'Beneficiario Dueño']);
    $student = User::factory()->create([
        'name' => 'Alumno Postulante',
        'email' => 'alumno@test.com',
    ]);

    $idea = ProjectIdea::factory()->create([
        'organizer_id' => $owner->id,
        'status' => 'available',
    ]);

    $assignment = Assignment::factory()->create([
        'project_idea_id' => $idea->id,
        'student_id' => $student->id,
    ]);

    expect($professor->can('view', $assignment))->toBeTrue();

    $this->actingAs($professor)->get("/ideas/{$idea->id}")
        ->assertOk()
        ->assertSee('Postulaciones')
        ->assertSee('Alumno Postulante')
        ->assertSee('alumno@test.com')
        ->assertSee($assignment->applied_at->format('d/m/Y'))
        ->assertDontSee($assignment->applied_at->format('d/m/Y H:i'));

    $this->actingAs($professor)->get('/assignments')
        ->assertOk()
        ->assertSee('Alumno Postulante');
});

test('el dashboard del profesor no muestra módulos', function () {
    $professor = User::factory()->create();
    $professor->assignRole('profesor');

    $this->actingAs($professor)->get('/dashboard')
        ->assertOk()
        ->assertSee('Dashboard')
        ->assertDontSee('Banco de Ideas y Proyectos de Software')
        ->assertDontSee('Lista y revisa el detalle de las ideas y proyectos.')
        ->assertDontSee('Actualiza tus datos y tu contraseña.')
        ->assertDontSee('Todas las postulaciones');
});

test('un estudiante no ve la lista de postulantes', function () {
    $student = User::factory()->create(['name' => 'Alumno Postulante']);
    $student->assignRole('student');

    $owner = User::factory()->create(['name' => 'Beneficiario Dueño']);

    $idea = ProjectIdea::factory()->create([
        'organizer_id' => $owner->id,
        'status' => 'available',
    ]);

    Assignment::factory()->create([
        'project_idea_id' => $idea->id,
        'student_id' => $student->id,
    ]);

    $this->actingAs($student)->get("/ideas/{$idea->id}")
        ->assertOk()
        ->assertSee('Ya postulado')
        ->assertDontSee('Postulaciones (1)')
        ->assertDontSee('alumno@test.com');
});
