<?php

use App\Models\Assignment;
use App\Models\ProjectIdea;
use App\Models\ProjectQuestion;
use App\Models\ProjectRequirement;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * Crea una idea disponible con un alumno aceptado en el equipo.
 *
 * @return array{0: ProjectIdea, 1: User, 2: User}
 */
function makeAnalysisScenario(int $maxStudents = 2, string $assignmentStatus = 'accepted'): array
{
    $beneficiary = User::factory()->create();
    $beneficiary->assignRole('beneficiario');

    $student = User::factory()->create();
    $student->assignRole('student');

    $idea = ProjectIdea::factory()->create([
        'organizer_id' => $beneficiary->id,
        'status' => 'available',
        'max_students' => $maxStudents,
    ]);

    Assignment::factory()->create([
        'project_idea_id' => $idea->id,
        'student_id' => $student->id,
        'status' => $assignmentStatus,
    ]);

    return [$idea, $beneficiary, $student];
}

test('un alumno aceptado pregunta y el beneficiario responde', function () {
    [$idea, $beneficiary, $student] = makeAnalysisScenario();

    $this->actingAs($student)
        ->post("/ideas/{$idea->id}/preguntas", [
            'question' => "1. ¿Qué información debe mostrar cada ficha?\n2. ¿Quién actualiza el estado del animal?",
        ])
        ->assertRedirect()
        ->assertSessionHas('status', 'Preguntas enviadas al beneficiario.');

    $question = ProjectQuestion::where('project_idea_id', $idea->id)->firstOrFail();

    expect($question->asked_by)->toBe($student->id)
        ->and($question->question)->toContain('1. ¿Qué información debe mostrar cada ficha?')
        ->and($question->question)->toContain('2. ¿Quién actualiza el estado del animal?')
        ->and($question->isAnswered())->toBeFalse();

    expect($beneficiary->notifications()->count())->toBe(1)
        ->and($beneficiary->notifications()->first()->data['title'])->toBe('Nuevas consultas del equipo')
        ->and($beneficiary->notifications()->first()->data['message'])->toContain($idea->title);

    $this->actingAs($beneficiary)
        ->patch("/preguntas/{$question->id}/responder", [
            'answer' => 'Nombre, edad, foto y estado de salud.',
        ])
        ->assertRedirect()
        ->assertSessionHas('status', 'Respuesta enviada al equipo.');

    $question->refresh();

    expect($question->answer)->toBe('Nombre, edad, foto y estado de salud.')
        ->and($question->answered_by)->toBe($beneficiary->id)
        ->and($question->isAnswered())->toBeTrue();

    expect($student->notifications()->count())->toBe(1)
        ->and($student->notifications()->first()->data['title'])->toBe('Respuesta del beneficiario');
});

test('el detalle de la idea muestra el analisis al equipo, al beneficiario y al profesor', function () {
    [$idea, $beneficiary, $student] = makeAnalysisScenario();

    $professor = User::factory()->create();
    $professor->assignRole('profesor');

    $this->actingAs($student)->get("/ideas/{$idea->id}")
        ->assertOk()
        ->assertSee('Análisis y requerimientos')
        ->assertSee('Nuevas preguntas para el beneficiario')
        ->assertSee('Lista de requerimientos del sistema');

    $this->actingAs($beneficiary)->get("/ideas/{$idea->id}")
        ->assertOk()
        ->assertSee('Preguntas al beneficiario');

    $this->actingAs($professor)->get("/ideas/{$idea->id}")
        ->assertOk()
        ->assertSee('Lista de requerimientos del sistema');
});

test('un alumno con postulacion pendiente no puede preguntar', function () {
    [$idea, , $student] = makeAnalysisScenario(assignmentStatus: 'applied');

    $this->actingAs($student)
        ->post("/ideas/{$idea->id}/preguntas", [
            'question' => '¿Puedo preguntar?',
        ])
        ->assertForbidden();

    expect(ProjectQuestion::count())->toBe(0);
});

test('solo el beneficiario dueño responde las preguntas', function () {
    [$idea, , $student] = makeAnalysisScenario();

    $otherBeneficiary = User::factory()->create();
    $otherBeneficiary->assignRole('beneficiario');

    $question = ProjectQuestion::factory()->create([
        'project_idea_id' => $idea->id,
        'asked_by' => $student->id,
    ]);

    $this->actingAs($otherBeneficiary)
        ->patch("/preguntas/{$question->id}/responder", [
            'answer' => 'Respuesta no autorizada.',
        ])
        ->assertForbidden();

    expect($question->fresh()->isAnswered())->toBeFalse();
});

test('el equipo guarda el borrador, lo envia y requiere la aprobacion del beneficiario y del profesor', function () {
    [$idea, $beneficiary, $student] = makeAnalysisScenario(maxStudents: 1);

    $professor = User::factory()->create();
    $professor->assignRole('profesor');

    $this->actingAs($student)
        ->patch("/ideas/{$idea->id}/requerimientos", [
            'content' => "1. Catálogo de libros.\n2. Registro de préstamos.",
        ])
        ->assertRedirect()
        ->assertSessionHas('status', 'Borrador de requerimientos guardado.');

    $requirement = ProjectRequirement::where('project_idea_id', $idea->id)->firstOrFail();

    expect($requirement->isDraft())->toBeTrue()
        ->and($requirement->content)->toContain('Catálogo de libros');

    $this->actingAs($student)
        ->patch("/ideas/{$idea->id}/requerimientos/enviar")
        ->assertRedirect()
        ->assertSessionHas('status', 'Requerimientos enviados. Deben aprobarlos el beneficiario y el profesor.');

    expect($requirement->fresh()->isPending())->toBeTrue();

    expect($beneficiary->notifications()->count())->toBe(1)
        ->and($beneficiary->notifications()->first()->data['title'])->toBe('Requerimientos para aprobar')
        ->and($professor->notifications()->count())->toBe(1)
        ->and($professor->notifications()->first()->data['title'])->toBe('Requerimientos para aprobar');

    $this->actingAs($beneficiary)
        ->patch("/ideas/{$idea->id}/requerimientos/aprobar")
        ->assertRedirect()
        ->assertSessionHas('status', 'Aprobación registrada. Falta la aprobación del profesor.');

    expect($requirement->fresh()->hasBeneficiaryApproval())->toBeTrue()
        ->and($requirement->fresh()->hasProfessorApproval())->toBeFalse()
        ->and($idea->fresh()->status)->toBe('available');

    $this->actingAs($professor)
        ->patch("/ideas/{$idea->id}/requerimientos/aprobar")
        ->assertRedirect()
        ->assertSessionHas('status', 'Requerimientos aprobados por el beneficiario y el profesor. El proyecto pasó a desarrollo.');

    expect($requirement->fresh()->isApproved())->toBeTrue()
        ->and($requirement->fresh()->professor_approved_by)->toBe($professor->id)
        ->and($idea->fresh()->status)->toBe('in_development')
        ->and($student->notifications()->count())->toBe(1)
        ->and($student->notifications()->first()->data['title'])->toBe('Requerimientos aprobados');
});

test('el proyecto no inicia con una sola aprobacion de los requerimientos', function () {
    [$idea, $beneficiary] = makeAnalysisScenario(maxStudents: 1);

    $requirement = ProjectRequirement::factory()->pending()->create([
        'project_idea_id' => $idea->id,
    ]);

    $this->actingAs($beneficiary)
        ->patch("/ideas/{$idea->id}/requerimientos/aprobar")
        ->assertRedirect();

    expect($requirement->fresh()->isApproved())->toBeFalse()
        ->and($idea->fresh()->status)->toBe('available')
        ->and($idea->fresh()->requirementsApproved())->toBeFalse();
});

test('un alumno fuera del equipo no puede redactar requerimientos', function () {
    [$idea, , $acceptedStudent] = makeAnalysisScenario();

    $outsider = User::factory()->create();
    $outsider->assignRole('student');

    $this->actingAs($outsider)
        ->patch("/ideas/{$idea->id}/requerimientos", [
            'content' => 'Requerimientos no autorizados.',
        ])
        ->assertForbidden();

    expect(ProjectRequirement::where('project_idea_id', $idea->id)->exists())->toBeFalse();
});

test('una lista aprobada no se puede modificar', function () {
    [$idea, , $student] = makeAnalysisScenario(maxStudents: 3);

    $requirement = ProjectRequirement::factory()->approved()->create([
        'project_idea_id' => $idea->id,
        'content' => 'Requerimientos originales.',
    ]);

    $this->actingAs($student)
        ->patch("/ideas/{$idea->id}/requerimientos", [
            'content' => 'Requerimientos modificados.',
        ])
        ->assertRedirect()
        ->assertSessionHas('status', 'Los requerimientos ya fueron aprobados y no se pueden modificar.');

    expect($requirement->fresh()->content)->toBe('Requerimientos originales.')
        ->and($requirement->fresh()->isApproved())->toBeTrue();
});

test('el beneficiario no puede aprobar requerimientos de una idea ajena', function () {
    [$idea] = makeAnalysisScenario(maxStudents: 1);

    ProjectRequirement::factory()->pending()->create([
        'project_idea_id' => $idea->id,
    ]);

    $otherBeneficiary = User::factory()->create();
    $otherBeneficiary->assignRole('beneficiario');

    $this->actingAs($otherBeneficiary)
        ->patch("/ideas/{$idea->id}/requerimientos/aprobar")
        ->assertForbidden();
});
