<?php

use App\Models\Assignment;
use App\Models\ProjectIdea;
use App\Models\ProjectUpdate;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $permissions = [
        'ViewAny:ProjectIdea', 'View:ProjectIdea',
        'ViewAny:Assignment', 'View:Assignment', 'Update:Assignment',
        'ViewAny:ProjectUpdate', 'View:ProjectUpdate', 'Update:ProjectUpdate',
    ];

    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => 'profesor', 'guard_name' => 'web'])
        ->syncPermissions($permissions);

    Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web'])
        ->syncPermissions(['ViewAny:ProjectIdea', 'View:ProjectIdea', 'ViewAny:Assignment', 'View:Assignment']);

    Role::firstOrCreate(['name' => 'beneficiario', 'guard_name' => 'web']);

    $this->professor = User::factory()->create(['name' => 'Profesor Panel']);
    $this->professor->assignRole('profesor');

    $this->beneficiary = User::factory()->create(['name' => 'Beneficiario del Proyecto']);
    $this->beneficiary->assignRole('beneficiario');

    $this->idea = ProjectIdea::factory()->create([
        'organizer_id' => $this->beneficiary->id,
        'title' => 'Proyecto de prueba del panel',
        'status' => 'available',
    ]);
});

function makeDashboardUpdate(Assignment $assignment, array $attributes = []): ProjectUpdate
{
    return ProjectUpdate::create(array_merge([
        'assignment_id' => $assignment->id,
        'project_idea_id' => $assignment->project_idea_id,
        'student_id' => $assignment->student_id,
        'description' => 'Avance de prueba para el panel.',
        'status' => ProjectUpdate::STATUS_PENDING,
    ], $attributes));
}

test('el dashboard del profesor muestra el grafico de estados de los proyectos', function () {
    ProjectIdea::factory()->create(['status' => 'available']);
    ProjectIdea::factory()->create(['status' => 'in_development']);
    ProjectIdea::factory()->create(['status' => 'completed']);

    $this->actingAs($this->professor)->get('/dashboard')
        ->assertOk()
        ->assertSee('Estado de los proyectos')
        ->assertSee('Pendientes de aprobación')
        ->assertSee('En desarrollo')
        ->assertSee('Finalizados')
        ->assertSee('Distribución de proyectos: 2 Pendientes de aprobación, 1 En desarrollo, 1 Finalizados, 0 Cancelados')
        ->assertSee('50%');
});

test('el dashboard del profesor lista las postulaciones por evaluar', function () {
    $pendiente = User::factory()->create(['name' => 'Alumno Pendiente']);
    $pendiente->assignRole('student');
    $aceptado = User::factory()->create(['name' => 'Alumno Ya Aceptado']);
    $aceptado->assignRole('student');

    Assignment::factory()->create([
        'project_idea_id' => $this->idea->id,
        'student_id' => $pendiente->id,
        'status' => 'applied',
        'applied_at' => now(),
    ]);

    Assignment::factory()->create([
        'project_idea_id' => $this->idea->id,
        'student_id' => $aceptado->id,
        'status' => 'accepted',
        'applied_at' => now(),
    ]);

    $this->actingAs($this->professor)->get('/dashboard')
        ->assertOk()
        ->assertSee('Postulaciones por evaluar')
        ->assertSee('Alumno Pendiente')
        ->assertDontSee('Alumno Ya Aceptado')
        ->assertSee('Proyecto de prueba del panel')
        ->assertSee('Evaluar')
        ->assertSee('status=applied', false);
});

test('el dashboard del profesor lista los avances pendientes de revision', function () {
    $pendiente = User::factory()->create(['name' => 'Alumno Con Avance']);
    $pendiente->assignRole('student');
    $aprobado = User::factory()->create(['name' => 'Alumno Ya Aprobado']);
    $aprobado->assignRole('student');

    $assignmentPendiente = Assignment::factory()->create([
        'project_idea_id' => $this->idea->id,
        'student_id' => $pendiente->id,
        'status' => 'accepted',
    ]);
    $assignmentAprobado = Assignment::factory()->create([
        'project_idea_id' => $this->idea->id,
        'student_id' => $aprobado->id,
        'status' => 'accepted',
    ]);

    makeDashboardUpdate($assignmentPendiente);
    makeDashboardUpdate($assignmentAprobado, ['status' => ProjectUpdate::STATUS_APPROVED, 'approved_at' => now()]);

    $this->actingAs($this->professor)->get('/dashboard')
        ->assertOk()
        ->assertSee('Avances enviados')
        ->assertSee('Alumno Con Avance')
        ->assertDontSee('Alumno Ya Aprobado')
        ->assertSee('Revisar')
        ->assertSee('Aprobar')
        ->assertSee('avance-', false);
});

test('el profesor puede aceptar una postulacion desde el modulo', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    $assignment = Assignment::factory()->create([
        'project_idea_id' => $this->idea->id,
        'student_id' => $student->id,
        'status' => 'applied',
    ]);

    $this->actingAs($this->professor)
        ->patch("/assignments/{$assignment->id}/estado", ['status' => 'accepted'])
        ->assertRedirect()
        ->assertSessionHas('status', 'Postulación aceptada.');

    expect($assignment->fresh()->status)->toBe('accepted');
});

test('el profesor puede rechazar una postulacion desde el modulo', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    $assignment = Assignment::factory()->create([
        'project_idea_id' => $this->idea->id,
        'student_id' => $student->id,
        'status' => 'applied',
    ]);

    $this->actingAs($this->professor)
        ->patch("/assignments/{$assignment->id}/estado", ['status' => 'rejected'])
        ->assertRedirect()
        ->assertSessionHas('status', 'Postulación rechazada.');

    expect($assignment->fresh()->status)->toBe('rejected');
});

test('una postulacion ya evaluada no se puede volver a evaluar', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    $assignment = Assignment::factory()->create([
        'project_idea_id' => $this->idea->id,
        'student_id' => $student->id,
        'status' => 'accepted',
    ]);

    $this->actingAs($this->professor)
        ->patch("/assignments/{$assignment->id}/estado", ['status' => 'rejected'])
        ->assertRedirect()
        ->assertSessionHas('status', 'Esta postulación ya fue evaluada.');

    expect($assignment->fresh()->status)->toBe('accepted');
});

test('un alumno no puede aceptar ni rechazar postulaciones', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    $assignment = Assignment::factory()->create([
        'project_idea_id' => $this->idea->id,
        'student_id' => $student->id,
        'status' => 'applied',
    ]);

    $this->actingAs($student)
        ->patch("/assignments/{$assignment->id}/estado", ['status' => 'accepted'])
        ->assertForbidden();

    expect($assignment->fresh()->status)->toBe('applied');
});

test('el modulo de postulaciones filtra por estado pendiente', function () {
    $pendiente = User::factory()->create(['name' => 'Alumno Pendiente']);
    $pendiente->assignRole('student');
    $aceptado = User::factory()->create(['name' => 'Alumno Aceptado']);
    $aceptado->assignRole('student');

    Assignment::factory()->create([
        'project_idea_id' => $this->idea->id,
        'student_id' => $pendiente->id,
        'status' => 'applied',
    ]);
    Assignment::factory()->create([
        'project_idea_id' => $this->idea->id,
        'student_id' => $aceptado->id,
        'status' => 'accepted',
    ]);

    $this->actingAs($this->professor)->get('/assignments?status=applied')
        ->assertOk()
        ->assertSee('Mostrando postulaciones:')
        ->assertSee('Alumno Pendiente')
        ->assertDontSee('Alumno Aceptado')
        ->assertSee('Aceptar')
        ->assertSee('Rechazar');
});
