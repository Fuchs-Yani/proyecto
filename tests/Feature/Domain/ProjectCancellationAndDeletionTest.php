<?php

use App\Models\Assignment;
use App\Models\Attachment;
use App\Models\ProjectIdea;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $perms = [
        'ViewAny:ProjectIdea', 'View:ProjectIdea', 'Create:ProjectIdea', 'Update:ProjectIdea', 'Delete:ProjectIdea',
        'ViewAny:Assignment', 'View:Assignment', 'Create:Assignment', 'Update:Assignment', 'Delete:Assignment',
        'ViewAny:Attachment', 'View:Attachment', 'Create:Attachment', 'Delete:Attachment',
    ];

    foreach ($perms as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => 'beneficiario', 'guard_name' => 'web'])
        ->syncPermissions([
            'ViewAny:ProjectIdea', 'View:ProjectIdea', 'Create:ProjectIdea', 'Update:ProjectIdea', 'Delete:ProjectIdea',
            'ViewAny:Assignment', 'View:Assignment', 'Update:Assignment',
            'ViewAny:Attachment', 'View:Attachment', 'Create:Attachment', 'Delete:Attachment',
        ]);

    Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web'])
        ->syncPermissions([
            'ViewAny:ProjectIdea', 'View:ProjectIdea',
            'ViewAny:Assignment', 'View:Assignment', 'Create:Assignment', 'Delete:Assignment',
            'ViewAny:Attachment', 'View:Attachment',
        ]);

    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());
});

function projectCancellationBeneficiario(): User
{
    $user = User::factory()->create();
    $user->assignRole('beneficiario');

    return $user;
}

test('una idea cancelada no acepta postulaciones', function (string $status) {
    $owner = projectCancellationBeneficiario();
    $student = User::factory()->create();
    $student->assignRole('student');

    $idea = ProjectIdea::factory()->create([
        'organizer_id' => $owner->id,
        'status' => $status,
    ]);

    $this->actingAs($student)
        ->post("/ideas/{$idea->id}/apply")
        ->assertRedirect()
        ->assertSessionHas('status', 'Esta idea ya no acepta postulaciones.');

    expect(Assignment::where('project_idea_id', $idea->id)->count())->toBe(0);
})->with(['cancelled', 'completed', 'in_development']);

test('una idea disponible si acepta postulaciones', function () {
    $owner = projectCancellationBeneficiario();
    $student = User::factory()->create();
    $student->assignRole('student');

    $idea = ProjectIdea::factory()->create([
        'organizer_id' => $owner->id,
        'status' => 'available',
        'max_students' => 2,
    ]);

    $this->actingAs($student)->post("/ideas/{$idea->id}/apply")->assertRedirect();

    expect(Assignment::where('project_idea_id', $idea->id)->count())->toBe(1);
});

test('el detalle de una idea cancelada no muestra el boton postularme', function () {
    $owner = projectCancellationBeneficiario();
    $student = User::factory()->create();
    $student->assignRole('student');

    $idea = ProjectIdea::factory()->create([
        'organizer_id' => $owner->id,
        'status' => 'cancelled',
    ]);

    $this->actingAs($student)->get("/ideas/{$idea->id}")
        ->assertOk()
        ->assertSee('Cancelado')
        ->assertSee('Esta idea ya no acepta postulaciones.')
        ->assertDontSee('Postularme');
});

test('el detalle de una idea disponible muestra el boton postularme', function () {
    $owner = projectCancellationBeneficiario();
    $student = User::factory()->create();
    $student->assignRole('student');

    $idea = ProjectIdea::factory()->create([
        'organizer_id' => $owner->id,
        'status' => 'available',
        'max_students' => 2,
    ]);

    $this->actingAs($student)->get("/ideas/{$idea->id}")
        ->assertOk()
        ->assertSee('Postularme');
});

test('el beneficiario elimina su idea sin postulaciones y desaparece de la base de datos', function () {
    Storage::fake('public');

    $owner = projectCancellationBeneficiario();

    $idea = ProjectIdea::factory()->create([
        'organizer_id' => $owner->id,
        'title' => 'Idea que se va a borrar',
    ]);

    $attachment = Attachment::factory()->create(['project_idea_id' => $idea->id]);
    Storage::disk('public')->put($attachment->file_path, 'contenido');

    $this->actingAs($owner)
        ->delete("/ideas/{$idea->id}")
        ->assertRedirect(route('ideas.index'))
        ->assertSessionHas('status', 'Idea eliminada correctamente.');

    expect(ProjectIdea::find($idea->id))->toBeNull()
        ->and(Attachment::where('project_idea_id', $idea->id)->count())->toBe(0);

    Storage::disk('public')->assertMissing($attachment->file_path);

    $this->actingAs($owner)->get('/ideas')->assertDontSee('Idea que se va a borrar');
});

test('el beneficiario no puede eliminar una idea si ya hay postulaciones', function (string $assignmentStatus) {
    $owner = projectCancellationBeneficiario();
    $student = User::factory()->create();
    $student->assignRole('student');

    $idea = ProjectIdea::factory()->create([
        'organizer_id' => $owner->id,
        'title' => 'Idea con postulaciones',
    ]);

    Assignment::factory()->create([
        'project_idea_id' => $idea->id,
        'student_id' => $student->id,
        'status' => $assignmentStatus,
    ]);

    $this->actingAs($owner)
        ->delete("/ideas/{$idea->id}")
        ->assertForbidden();

    expect(ProjectIdea::find($idea->id))->not->toBeNull();
})->with(['applied', 'accepted', 'in_progress', 'rejected', 'withdrawn']);

test('el beneficiario no puede eliminar una idea en desarrollo o terminada', function (string $status) {
    $owner = projectCancellationBeneficiario();

    $idea = ProjectIdea::factory()->create([
        'organizer_id' => $owner->id,
        'status' => $status,
    ]);

    $this->actingAs($owner)
        ->delete("/ideas/{$idea->id}")
        ->assertForbidden();

    expect(ProjectIdea::find($idea->id))->not->toBeNull();
})->with(['in_development', 'completed']);

test('el beneficiario cancela una idea con postulaciones indicando el motivo', function () {
    $owner = projectCancellationBeneficiario();
    $student = User::factory()->create();
    $student->assignRole('student');

    $idea = ProjectIdea::factory()->create([
        'organizer_id' => $owner->id,
        'status' => 'in_development',
    ]);

    $assignment = Assignment::factory()->create([
        'project_idea_id' => $idea->id,
        'student_id' => $student->id,
        'status' => Assignment::STATUS_ACCEPTED,
    ]);

    $this->actingAs($owner)
        ->patch("/ideas/{$idea->id}/cancelar", [
            'cancellation_reason' => 'La institución ya no necesita el desarrollo.',
        ])
        ->assertRedirect()
        ->assertSessionHas('status', 'Idea cancelada. Se conservaron las postulaciones y los avances.');

    $idea->refresh();

    expect($idea->status)->toBe('cancelled')
        ->and($idea->cancellation_reason)->toBe('La institución ya no necesita el desarrollo.')
        ->and($idea->cancelled_at)->not->toBeNull()
        ->and(Assignment::find($assignment->id))->not->toBeNull();
});

test('cancelar una idea rechaza las postulaciones pendientes y conserva las aceptadas', function () {
    $owner = projectCancellationBeneficiario();
    $pendingStudent = User::factory()->create();
    $acceptedStudent = User::factory()->create();

    $idea = ProjectIdea::factory()->create([
        'organizer_id' => $owner->id,
        'status' => 'available',
    ]);

    $pending = Assignment::factory()->create([
        'project_idea_id' => $idea->id,
        'student_id' => $pendingStudent->id,
        'status' => Assignment::STATUS_APPLIED,
    ]);

    $accepted = Assignment::factory()->create([
        'project_idea_id' => $idea->id,
        'student_id' => $acceptedStudent->id,
        'status' => Assignment::STATUS_ACCEPTED,
    ]);

    $this->actingAs($owner)
        ->patch("/ideas/{$idea->id}/cancelar", [
            'cancellation_reason' => 'El proyecto se detuvo por falta de presupuesto.',
        ])
        ->assertRedirect();

    expect($pending->fresh()->status)->toBe(Assignment::STATUS_REJECTED)
        ->and($accepted->fresh()->status)->toBe(Assignment::STATUS_ACCEPTED)
        ->and($idea->fresh()->status)->toBe('cancelled');
});

test('no se puede cancelar una idea sin indicar el motivo', function () {
    $owner = projectCancellationBeneficiario();

    $idea = ProjectIdea::factory()->create([
        'organizer_id' => $owner->id,
        'status' => 'available',
    ]);

    $this->actingAs($owner)
        ->patch("/ideas/{$idea->id}/cancelar", ['cancellation_reason' => ''])
        ->assertSessionHasErrors('cancellation_reason');

    expect($idea->fresh()->status)->toBe('available');
});

test('el motivo de cancelacion se muestra en el detalle de la idea', function () {
    $owner = projectCancellationBeneficiario();

    $idea = ProjectIdea::factory()->create([
        'organizer_id' => $owner->id,
        'status' => 'cancelled',
        'cancellation_reason' => 'La necesidad fue cubierta por otra institución.',
        'cancelled_at' => now(),
    ]);

    $this->actingAs($owner)
        ->get("/ideas/{$idea->id}")
        ->assertOk()
        ->assertSee('Motivo de cancelación:')
        ->assertSee('La necesidad fue cubierta por otra institución.');
});

test('el detalle no ofrece eliminar una idea con postulaciones pero si cancelarla', function () {
    $owner = projectCancellationBeneficiario();
    $student = User::factory()->create();
    $student->assignRole('student');

    $idea = ProjectIdea::factory()->create([
        'organizer_id' => $owner->id,
        'title' => 'Idea con trabajo asociado',
    ]);

    Assignment::factory()->create([
        'project_idea_id' => $idea->id,
        'student_id' => $student->id,
        'status' => Assignment::STATUS_APPLIED,
    ]);

    $this->actingAs($owner)
        ->get("/ideas/{$idea->id}")
        ->assertOk()
        ->assertDontSee('Eliminar idea')
        ->assertSee('Cancelar idea')
        ->assertSee('Motivo de la cancelación');
});

test('un beneficiario no puede eliminar la idea de otro', function () {
    $owner = projectCancellationBeneficiario();
    $other = projectCancellationBeneficiario();

    $idea = ProjectIdea::factory()->create(['organizer_id' => $owner->id]);

    $this->actingAs($other)
        ->delete("/ideas/{$idea->id}")
        ->assertForbidden();

    expect(ProjectIdea::find($idea->id))->not->toBeNull();
});

test('un estudiante no puede eliminar ideas', function () {
    $owner = projectCancellationBeneficiario();
    $student = User::factory()->create();
    $student->assignRole('student');

    $idea = ProjectIdea::factory()->create(['organizer_id' => $owner->id]);

    $this->actingAs($student)
        ->delete("/ideas/{$idea->id}")
        ->assertForbidden();

    expect(ProjectIdea::find($idea->id))->not->toBeNull();
});

test('el admin puede eliminar cualquier idea', function () {
    $owner = projectCancellationBeneficiario();
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $idea = ProjectIdea::factory()->create(['organizer_id' => $owner->id]);

    $this->actingAs($admin)
        ->delete("/ideas/{$idea->id}")
        ->assertRedirect(route('ideas.index'));

    expect(ProjectIdea::find($idea->id))->toBeNull();
});

test('el aviso de idea eliminada se muestra como toast descartable', function () {
    $owner = projectCancellationBeneficiario();
    $idea = ProjectIdea::factory()->create([
        'organizer_id' => $owner->id,
        'title' => 'Idea con aviso',
    ]);

    $this->actingAs($owner)
        ->followingRedirects()
        ->delete("/ideas/{$idea->id}")
        ->assertOk()
        ->assertSee('Idea eliminada correctamente.')
        ->assertSee('x-init="setTimeout(() => visible = false, 4000)"', false);
});

test('el listado sin aviso pendiente no muestra toast', function () {
    $owner = projectCancellationBeneficiario();

    $this->actingAs($owner)
        ->get('/ideas')
        ->assertOk()
        ->assertDontSee('x-init="setTimeout(() => visible = false, 4000)"', false);
});

test('un invitado no puede eliminar ideas', function () {
    $owner = projectCancellationBeneficiario();
    $idea = ProjectIdea::factory()->create(['organizer_id' => $owner->id]);

    $this->delete("/ideas/{$idea->id}")->assertRedirect(route('login'));

    expect(ProjectIdea::find($idea->id))->not->toBeNull();
});
