<?php

use App\Models\Assignment;
use App\Models\ProjectIdea;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $permissions = [
        'ViewAny:Assignment', 'View:Assignment', 'Create:Assignment', 'Update:Assignment', 'Delete:Assignment',
        'ViewAny:ProjectIdea', 'View:ProjectIdea', 'Update:ProjectIdea',
    ];

    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web'])
        ->syncPermissions([
            'ViewAny:Assignment', 'View:Assignment', 'Create:Assignment', 'Delete:Assignment',
            'ViewAny:ProjectIdea', 'View:ProjectIdea',
        ]);

    Role::firstOrCreate(['name' => 'beneficiario', 'guard_name' => 'web'])
        ->syncPermissions(['ViewAny:Assignment', 'View:Assignment', 'Update:Assignment', 'ViewAny:ProjectIdea', 'View:ProjectIdea']);

    Role::firstOrCreate(['name' => 'profesor', 'guard_name' => 'web'])
        ->syncPermissions([
            'ViewAny:Assignment', 'View:Assignment', 'Update:Assignment',
            'ViewAny:ProjectIdea', 'View:ProjectIdea',
        ]);
});

test('apply sets applied_at and status applied', function () {
    $org = User::factory()->create();
    $student = User::factory()->create();
    $student->assignRole('student');
    $idea = ProjectIdea::factory()->create([
        'organizer_id' => $org->id,
        'max_students' => 2,
    ]);

    $this->actingAs($student)->post("/ideas/{$idea->id}/apply");

    $a = Assignment::where('student_id', $student->id)->first();
    expect($a)->not->toBeNull()
        ->and($a->status)->toBe('applied')
        ->and($a->applied_at)->not->toBeNull();
});

test('no se puede postular hasta que el profesor defina el cupo', function () {
    $org = User::factory()->create();
    $student = User::factory()->create();
    $student->assignRole('student');
    $idea = ProjectIdea::factory()->create([
        'organizer_id' => $org->id,
        'max_students' => null,
    ]);

    $this->actingAs($student)
        ->post("/ideas/{$idea->id}/apply")
        ->assertSessionHas('status', 'El profesor todavía no definió el cupo para esta idea.');

    expect(Assignment::where('project_idea_id', $idea->id)->count())->toBe(0);
});

test('status transition sets started_at and finished_at', function () {
    $a = Assignment::factory()->create(['status' => 'accepted']);

    $a->update(['status' => 'in_progress', 'started_at' => now()]);
    expect($a->fresh()->started_at)->not->toBeNull();

    $a->update(['status' => 'completed', 'finished_at' => now()]);
    expect($a->fresh()->finished_at)->not->toBeNull();
});

test('el beneficiario dueño de la idea no puede aceptar ni rechazar postulaciones', function () {
    $beneficiary = User::factory()->create();
    $beneficiary->assignRole('beneficiario');
    $student = User::factory()->create();
    $student->assignRole('student');
    $idea = ProjectIdea::factory()->create([
        'organizer_id' => $beneficiary->id,
        'max_students' => 2,
    ]);
    $assignment = Assignment::factory()->create([
        'project_idea_id' => $idea->id,
        'student_id' => $student->id,
        'status' => 'applied',
    ]);

    $this->actingAs($beneficiary)
        ->patch("/assignments/{$assignment->id}/estado", ['status' => 'accepted'])
        ->assertForbidden();

    $this->actingAs($beneficiary)
        ->patch("/assignments/{$assignment->id}/estado", ['status' => 'rejected'])
        ->assertForbidden();

    expect($assignment->fresh()->status)->toBe('applied');
});

test('el proyecto pasa a desarrollo recien cuando el cupo esta completo y todos fueron aceptados', function () {
    $professor = User::factory()->create();
    $professor->assignRole('profesor');
    $idea = ProjectIdea::factory()->create([
        'status' => 'available',
        'max_students' => 2,
    ]);
    $first = Assignment::factory()->create([
        'project_idea_id' => $idea->id,
        'status' => 'applied',
    ]);
    $second = Assignment::factory()->create([
        'project_idea_id' => $idea->id,
        'status' => 'applied',
    ]);

    $this->actingAs($professor)
        ->patch("/assignments/{$first->id}/estado", ['status' => 'accepted'])
        ->assertRedirect()
        ->assertSessionHas('status', 'Postulación aceptada.');

    expect($first->fresh()->status)->toBe('accepted')
        ->and($first->fresh()->started_at)->not->toBeNull()
        ->and($idea->fresh()->status)->toBe('available');

    $this->actingAs($professor)
        ->patch("/assignments/{$second->id}/estado", ['status' => 'accepted'])
        ->assertRedirect()
        ->assertSessionHas('status', 'Postulación aceptada. El proyecto pasó a desarrollo.');

    expect($second->fresh()->status)->toBe('accepted')
        ->and($idea->fresh()->status)->toBe('in_development');
});

test('no se puede aceptar una postulacion si el profesor no definio el cupo', function () {
    $professor = User::factory()->create();
    $professor->assignRole('profesor');
    $assignment = Assignment::factory()->create(['status' => 'applied']);

    $this->actingAs($professor)
        ->patch("/assignments/{$assignment->id}/estado", ['status' => 'accepted'])
        ->assertRedirect()
        ->assertSessionHas('status', 'No se puede aceptar: el profesor todavía no definió el cupo o ya se completó el cupo de aceptados.');

    expect($assignment->fresh()->status)->toBe('applied');
});

test('no se pueden aceptar mas postulantes que el cupo definido', function () {
    $professor = User::factory()->create();
    $professor->assignRole('profesor');
    $idea = ProjectIdea::factory()->create([
        'status' => 'available',
        'max_students' => 2,
    ]);
    $assignments = collect(range(1, 3))->map(fn () => Assignment::factory()->create([
        'project_idea_id' => $idea->id,
        'status' => 'applied',
    ]));

    $this->actingAs($professor)->patch("/assignments/{$assignments[0]->id}/estado", ['status' => 'accepted']);
    $this->actingAs($professor)->patch("/assignments/{$assignments[1]->id}/estado", ['status' => 'accepted']);
    $this->actingAs($professor)
        ->patch("/assignments/{$assignments[2]->id}/estado", ['status' => 'accepted'])
        ->assertSessionHas('status', 'No se puede aceptar: el profesor todavía no definió el cupo o ya se completó el cupo de aceptados.');

    expect($assignments[2]->fresh()->status)->toBe('applied')
        ->and($idea->fresh()->acceptedAssignmentsCount())->toBe(2);
});

test('el profesor define el cupo solamente entre 1 y 4 alumnos', function () {
    $professor = User::factory()->create();
    $professor->assignRole('profesor');
    $idea = ProjectIdea::factory()->create(['max_students' => null]);

    $this->actingAs($professor)
        ->patch("/ideas/{$idea->id}/cupo", ['max_students' => 0])
        ->assertSessionHasErrors('max_students');

    $this->actingAs($professor)
        ->patch("/ideas/{$idea->id}/cupo", ['max_students' => 5])
        ->assertSessionHasErrors('max_students');

    $this->actingAs($professor)
        ->patch("/ideas/{$idea->id}/cupo", ['max_students' => 1])
        ->assertRedirect()
        ->assertSessionHas('status', 'Cupo definido: 1 alumno.');

    $this->actingAs($professor)
        ->patch("/ideas/{$idea->id}/cupo", ['max_students' => 4])
        ->assertRedirect()
        ->assertSessionHas('status', 'Cupo definido: 4 alumnos.');

    expect($idea->fresh()->max_students)->toBe(4);
});

test('con cupo de un alumno el proyecto pasa a desarrollo al aceptarlo', function () {
    $professor = User::factory()->create();
    $professor->assignRole('profesor');
    $idea = ProjectIdea::factory()->create([
        'status' => 'available',
        'max_students' => 1,
    ]);
    $assignment = Assignment::factory()->create([
        'project_idea_id' => $idea->id,
        'status' => 'applied',
    ]);

    $this->actingAs($professor)
        ->patch("/assignments/{$assignment->id}/estado", ['status' => 'accepted'])
        ->assertSessionHas('status', 'Postulación aceptada. El proyecto pasó a desarrollo.');

    expect($assignment->fresh()->status)->toBe('accepted')
        ->and($idea->fresh()->status)->toBe('in_development');
});

test('el profesor inicia el proyecto con los aceptados aunque el cupo no este completo', function () {
    $professor = User::factory()->create();
    $professor->assignRole('profesor');
    $idea = ProjectIdea::factory()->create([
        'status' => 'available',
        'max_students' => 4,
    ]);
    $accepted = Assignment::factory()->create([
        'project_idea_id' => $idea->id,
        'status' => 'applied',
    ]);
    $pending = Assignment::factory()->create([
        'project_idea_id' => $idea->id,
        'status' => 'applied',
    ]);

    $this->actingAs($professor)
        ->patch("/assignments/{$accepted->id}/estado", ['status' => 'accepted']);

    expect($idea->fresh()->status)->toBe('available');

    $this->actingAs($professor)
        ->patch("/ideas/{$idea->id}/iniciar")
        ->assertRedirect()
        ->assertSessionHas('status', 'Proyecto iniciado con 1 integrante. Se rechazó 1 postulación pendiente.');

    expect($idea->fresh()->status)->toBe('in_development')
        ->and($accepted->fresh()->status)->toBe('accepted')
        ->and($pending->fresh()->status)->toBe('rejected');
});

test('el cartel de inicio aclara cuantas postulaciones pendientes se rechazaran', function () {
    $professor = User::factory()->create();
    $professor->assignRole('profesor');
    $idea = ProjectIdea::factory()->create([
        'title' => 'Proyecto con cupo completo entre aceptados y pendientes',
        'status' => 'available',
        'max_students' => 2,
    ]);
    Assignment::factory()->create(['project_idea_id' => $idea->id, 'status' => 'accepted']);
    Assignment::factory()->create(['project_idea_id' => $idea->id, 'status' => 'applied']);

    // El cupo de 2 está ocupado por 1 aceptado y 1 pendiente, pero el cartel
    // debe aclarar que solo hay 1 integrante confirmado.
    $this->actingAs($professor)->get('/ideas')
        ->assertOk()
        ->assertSee('¿Iniciar el proyecto con 1 integrante aceptado? Se rechazará 1 postulación pendiente automáticamente.')
        ->assertDontSee('Las postulaciones pendientes serán rechazadas');
});

test('el cartel de inicio usa plural cuando hay varias postulaciones pendientes', function () {
    $professor = User::factory()->create();
    $professor->assignRole('profesor');
    $idea = ProjectIdea::factory()->create([
        'title' => 'Proyecto con varias postulaciones pendientes',
        'status' => 'available',
        'max_students' => 3,
    ]);
    Assignment::factory()->create(['project_idea_id' => $idea->id, 'status' => 'accepted']);
    Assignment::factory()->count(2)->create(['project_idea_id' => $idea->id, 'status' => 'applied']);

    $this->actingAs($professor)->get("/ideas/{$idea->id}")
        ->assertOk()
        ->assertSee('¿Iniciar el proyecto con 1 integrante aceptado? Se rechazarán 2 postulaciones pendientes automáticamente.');
});

test('el cartel de inicio no menciona rechazos cuando no hay postulaciones pendientes', function () {
    $professor = User::factory()->create();
    $professor->assignRole('profesor');
    $idea = ProjectIdea::factory()->create([
        'title' => 'Proyecto sin postulaciones pendientes',
        'status' => 'available',
        'max_students' => 3,
    ]);
    Assignment::factory()->create(['project_idea_id' => $idea->id, 'status' => 'accepted']);

    $this->actingAs($professor)->get('/ideas')
        ->assertOk()
        ->assertSee('¿Iniciar el proyecto con 1 integrante aceptado? El cupo definido es de 3 alumnos.')
        ->assertDontSee('Se rechazará');
});

test('no se puede iniciar un proyecto sin integrantes aceptados', function () {
    $professor = User::factory()->create();
    $professor->assignRole('profesor');
    $idea = ProjectIdea::factory()->create([
        'status' => 'available',
        'max_students' => 4,
    ]);
    Assignment::factory()->create([
        'project_idea_id' => $idea->id,
        'status' => 'applied',
    ]);

    $this->actingAs($professor)
        ->patch("/ideas/{$idea->id}/iniciar")
        ->assertSessionHasErrors('start');

    expect($idea->fresh()->status)->toBe('available');
});

test('un alumno no puede iniciar el proyecto', function () {
    $student = User::factory()->create();
    $student->assignRole('student');
    $idea = ProjectIdea::factory()->create([
        'status' => 'available',
        'max_students' => 4,
    ]);
    Assignment::factory()->create([
        'project_idea_id' => $idea->id,
        'status' => 'accepted',
    ]);

    $this->actingAs($student)
        ->patch("/ideas/{$idea->id}/iniciar")
        ->assertForbidden();

    expect($idea->fresh()->status)->toBe('available');
});

test('un alumno no puede definir el cupo', function () {
    $student = User::factory()->create();
    $student->assignRole('student');
    $idea = ProjectIdea::factory()->create(['max_students' => null]);

    $this->actingAs($student)
        ->patch("/ideas/{$idea->id}/cupo", ['max_students' => 3])
        ->assertForbidden();

    expect($idea->fresh()->max_students)->toBeNull();
});

test('el profesor ve la accion para analizar cada proyecto', function () {
    $professor = User::factory()->create();
    $professor->assignRole('profesor');
    $pending = ProjectIdea::factory()->create([
        'title' => 'Proyecto pendiente de cupo',
        'status' => 'available',
        'max_students' => null,
    ]);
    ProjectIdea::factory()->create([
        'title' => 'Proyecto con cupo definido',
        'status' => 'available',
        'max_students' => 2,
    ]);

    $this->actingAs($professor)->get('/ideas')
        ->assertOk()
        ->assertSee('Proyecto pendiente de cupo')
        ->assertSee('Proyecto con cupo definido')
        ->assertSee('Pendiente de revisión')
        ->assertSee('Analizar')
        ->assertSee('Cambiar cupo')
        ->assertSee('0/2 postulantes')
        ->assertSee(route('ideas.quota.update', $pending), false);
});

test('el alumno no ve la accion para analizar proyectos', function () {
    $student = User::factory()->create();
    $student->assignRole('student');
    ProjectIdea::factory()->create([
        'title' => 'Proyecto visible para alumnos',
        'status' => 'available',
        'max_students' => null,
    ]);

    $this->actingAs($student)->get('/ideas')
        ->assertOk()
        ->assertSee('Proyecto visible para alumnos')
        ->assertDontSee('Analizar')
        ->assertDontSee('Cambiar cupo');
});

test('el alumno puede retirar una postulacion pendiente mientras el proyecto esta disponible', function () {
    $student = User::factory()->create();
    $student->assignRole('student');
    $idea = ProjectIdea::factory()->create([
        'status' => 'available',
        'max_students' => 2,
    ]);
    $assignment = Assignment::factory()->create([
        'project_idea_id' => $idea->id,
        'student_id' => $student->id,
        'status' => 'applied',
    ]);

    $this->actingAs($student)
        ->delete("/assignments/{$assignment->id}")
        ->assertRedirect()
        ->assertSessionHas('status', 'Postulación eliminada.');

    expect(Assignment::find($assignment->id))->toBeNull();
});

test('el alumno no puede retirar una postulacion pendiente si el proyecto ya no esta disponible', function () {
    $student = User::factory()->create();
    $student->assignRole('student');
    $idea = ProjectIdea::factory()->create([
        'status' => 'in_development',
        'max_students' => 2,
    ]);
    $assignment = Assignment::factory()->create([
        'project_idea_id' => $idea->id,
        'student_id' => $student->id,
        'status' => 'applied',
    ]);

    $this->actingAs($student)
        ->delete("/assignments/{$assignment->id}")
        ->assertForbidden();

    expect(Assignment::find($assignment->id))->not->toBeNull();
});

test('el alumno no puede eliminar una postulacion aceptada, en progreso o completada', function (string $status) {
    $student = User::factory()->create();
    $student->assignRole('student');
    $idea = ProjectIdea::factory()->create([
        'status' => 'available',
        'max_students' => 2,
    ]);
    $assignment = Assignment::factory()->create([
        'project_idea_id' => $idea->id,
        'student_id' => $student->id,
        'status' => $status,
    ]);

    $this->actingAs($student)
        ->delete("/assignments/{$assignment->id}")
        ->assertForbidden();

    expect(Assignment::find($assignment->id))->not->toBeNull()
        ->and($assignment->fresh()->status)->toBe($status);
})->with(['accepted', 'in_progress', 'completed']);

test('el alumno puede abandonar una postulacion aceptada y se libera el cupo', function () {
    $student = User::factory()->create();
    $student->assignRole('student');
    $idea = ProjectIdea::factory()->create([
        'status' => 'available',
        'max_students' => 2,
    ]);
    $assignment = Assignment::factory()->create([
        'project_idea_id' => $idea->id,
        'student_id' => $student->id,
        'status' => 'accepted',
    ]);

    $this->actingAs($student)
        ->patch("/assignments/{$assignment->id}/abandonar")
        ->assertRedirect()
        ->assertSessionHas('status', 'Abandonaste el proyecto. Se liberó tu cupo.');

    expect($assignment->fresh()->status)->toBe('withdrawn')
        ->and($idea->fresh()->activeAssignmentsCount())->toBe(0)
        ->and($idea->fresh()->acceptedAssignmentsCount())->toBe(0)
        ->and($idea->fresh()->hasAvailableSlots())->toBeTrue();
});

test('abandonar una postulacion aceptada devuelve el proyecto a disponible si estaba en desarrollo', function () {
    $student = User::factory()->create();
    $student->assignRole('student');
    $otherStudent = User::factory()->create();
    $otherStudent->assignRole('student');
    $idea = ProjectIdea::factory()->create([
        'status' => 'in_development',
        'max_students' => 2,
    ]);
    $first = Assignment::factory()->create([
        'project_idea_id' => $idea->id,
        'student_id' => $student->id,
        'status' => 'accepted',
    ]);
    Assignment::factory()->create([
        'project_idea_id' => $idea->id,
        'student_id' => $otherStudent->id,
        'status' => 'accepted',
    ]);

    $this->actingAs($student)
        ->patch("/assignments/{$first->id}/abandonar")
        ->assertRedirect();

    expect($first->fresh()->status)->toBe('withdrawn')
        ->and($idea->fresh()->status)->toBe('available')
        ->and($idea->fresh()->activeAssignmentsCount())->toBe(1)
        ->and($idea->fresh()->acceptedAssignmentsCount())->toBe(1);
});

test('el alumno no puede abandonar una postulacion completada', function () {
    $student = User::factory()->create();
    $student->assignRole('student');
    $idea = ProjectIdea::factory()->create([
        'status' => 'completed',
        'max_students' => 2,
    ]);
    $assignment = Assignment::factory()->create([
        'project_idea_id' => $idea->id,
        'student_id' => $student->id,
        'status' => 'completed',
    ]);

    $this->actingAs($student)
        ->patch("/assignments/{$assignment->id}/abandonar")
        ->assertForbidden();

    expect($assignment->fresh()->status)->toBe('completed');
});
