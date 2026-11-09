<?php

use App\Models\Assignment;
use App\Models\ProjectIdea;
use App\Models\ProjectUpdate;
use App\Models\User;
use App\Notifications\ApplicationReviewed;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $permissions = [
        'ViewAny:ProjectIdea', 'View:ProjectIdea', 'Update:ProjectIdea',
        'ViewAny:Assignment', 'View:Assignment', 'Create:Assignment', 'Update:Assignment', 'Delete:Assignment',
        'ViewAny:ProjectUpdate', 'View:ProjectUpdate', 'Create:ProjectUpdate', 'Update:ProjectUpdate', 'Delete:ProjectUpdate',
    ];

    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web'])
        ->syncPermissions([
            'View:ProjectIdea',
            'ViewAny:Assignment', 'View:Assignment', 'Create:Assignment',
            'View:ProjectUpdate', 'Create:ProjectUpdate',
        ]);

    Role::firstOrCreate(['name' => 'beneficiario', 'guard_name' => 'web'])
        ->syncPermissions([
            'View:ProjectIdea',
            'ViewAny:Assignment', 'View:Assignment',
            'View:ProjectUpdate',
        ]);

    Role::firstOrCreate(['name' => 'profesor', 'guard_name' => 'web'])
        ->syncPermissions([
            'View:ProjectIdea',
            'ViewAny:Assignment', 'View:Assignment', 'Update:Assignment',
            'ViewAny:ProjectUpdate', 'View:ProjectUpdate', 'Update:ProjectUpdate',
        ]);

    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());
});

test('se notifica al alumno cuando aceptan su postulacion', function () {
    $professor = User::factory()->create();
    $professor->assignRole('profesor');

    $student = User::factory()->create();
    $student->assignRole('student');

    $idea = ProjectIdea::factory()->create([
        'title' => 'Sistema de biblioteca',
        'status' => 'available',
        'max_students' => 1,
    ]);

    $assignment = Assignment::factory()->create([
        'project_idea_id' => $idea->id,
        'student_id' => $student->id,
        'status' => 'applied',
    ]);

    $this->actingAs($professor)
        ->patch("/assignments/{$assignment->id}/estado", ['status' => 'accepted'])
        ->assertRedirect();

    $notification = $student->notifications()->first();

    expect($notification)->not->toBeNull()
        ->and($notification->data['title'])->toBe('Postulación aceptada')
        ->and($notification->data['status'])->toBe(Assignment::STATUS_ACCEPTED)
        ->and($notification->data['message'])->toContain('Sistema de biblioteca')
        ->and($notification->read_at)->toBeNull();
});

test('se notifica al alumno cuando rechazan su postulacion', function () {
    $professor = User::factory()->create();
    $professor->assignRole('profesor');

    $student = User::factory()->create();
    $student->assignRole('student');

    $assignment = Assignment::factory()->create([
        'student_id' => $student->id,
        'status' => 'applied',
    ]);

    $this->actingAs($professor)
        ->patch("/assignments/{$assignment->id}/estado", ['status' => 'rejected'])
        ->assertRedirect();

    $notification = $student->notifications()->first();

    expect($notification)->not->toBeNull()
        ->and($notification->data['title'])->toBe('Postulación rechazada')
        ->and($notification->data['status'])->toBe(Assignment::STATUS_REJECTED);
});

test('se notifica al beneficiario cuando el profesor revisa su idea y define el cupo', function () {
    $professor = User::factory()->create();
    $professor->assignRole('profesor');

    $beneficiary = User::factory()->create();
    $beneficiary->assignRole('beneficiario');

    $idea = ProjectIdea::factory()->create([
        'organizer_id' => $beneficiary->id,
        'title' => 'Huerta comunitaria',
        'status' => 'available',
        'max_students' => null,
    ]);

    $this->actingAs($professor)
        ->patch("/ideas/{$idea->id}/cupo", ['max_students' => 3])
        ->assertRedirect();

    $notification = $beneficiary->notifications()->first();

    expect($notification)->not->toBeNull()
        ->and($notification->data['title'])->toBe('Idea revisada')
        ->and($notification->data['max_students'])->toBe(3)
        ->and($notification->data['message'])->toContain('Huerta comunitaria');
});

test('se notifica al alumno cuando aprueban su avance', function () {
    [$professor, $student, $idea, $assignment] = notificationScenario();

    $update = ProjectUpdate::create([
        'assignment_id' => $assignment->id,
        'project_idea_id' => $idea->id,
        'student_id' => $student->id,
        'description' => 'Entrega del módulo de préstamos.',
        'status' => ProjectUpdate::STATUS_PENDING,
    ]);

    $this->actingAs($professor)
        ->patch("/avances/{$update->id}/aprobar")
        ->assertRedirect();

    $notification = $student->notifications()->first();

    expect($update->fresh()->status)->toBe(ProjectUpdate::STATUS_APPROVED)
        ->and($notification)->not->toBeNull()
        ->and($notification->data['title'])->toBe('Avance aprobado')
        ->and($notification->data['status'])->toBe(ProjectUpdate::STATUS_APPROVED);
});

test('se notifica al alumno cuando rechazan su avance', function () {
    [$professor, $student, $idea, $assignment] = notificationScenario();

    $update = ProjectUpdate::create([
        'assignment_id' => $assignment->id,
        'project_idea_id' => $idea->id,
        'student_id' => $student->id,
        'description' => 'Entrega incompleta.',
        'status' => ProjectUpdate::STATUS_PENDING,
    ]);

    $this->actingAs($professor)
        ->patch("/avances/{$update->id}/rechazar")
        ->assertRedirect();

    $notification = $student->notifications()->first();

    expect($update->fresh()->status)->toBe(ProjectUpdate::STATUS_REJECTED)
        ->and($notification)->not->toBeNull()
        ->and($notification->data['title'])->toBe('Avance rechazado')
        ->and($notification->data['status'])->toBe(ProjectUpdate::STATUS_REJECTED);
});

test('el alumno puede ver sus notificaciones y marcarlas como leidas', function () {
    $student = User::factory()->create();
    $student->assignRole('student');
    $assignment = Assignment::factory()->create([
        'student_id' => $student->id,
        'status' => 'accepted',
    ]);

    $student->notify(new ApplicationReviewed($assignment, Assignment::STATUS_ACCEPTED));

    $notification = $student->notifications()->first();

    $this->actingAs($student)->get('/notificaciones')
        ->assertOk()
        ->assertSee('Postulación aceptada');

    $this->actingAs($student)
        ->get("/notificaciones/{$notification->id}")
        ->assertRedirect(route('assignments.index'));

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('el alumno puede marcar todas sus notificaciones como leidas', function () {
    $student = User::factory()->create();
    $student->assignRole('student');
    $assignment = Assignment::factory()->create([
        'student_id' => $student->id,
        'status' => 'accepted',
    ]);

    $student->notify(new ApplicationReviewed($assignment, Assignment::STATUS_ACCEPTED));
    $student->notify(new ApplicationReviewed($assignment, Assignment::STATUS_REJECTED));

    $this->actingAs($student)
        ->patch('/notificaciones/leer-todas')
        ->assertRedirect();

    expect($student->unreadNotifications()->count())->toBe(0);
});

test('un usuario no puede leer las notificaciones de otro', function () {
    $student = User::factory()->create();
    $student->assignRole('student');
    $other = User::factory()->create();
    $other->assignRole('student');

    $assignment = Assignment::factory()->create([
        'student_id' => $student->id,
        'status' => 'accepted',
    ]);

    $student->notify(new ApplicationReviewed($assignment, Assignment::STATUS_ACCEPTED));
    $notification = $student->notifications()->first();

    $this->actingAs($other)
        ->get("/notificaciones/{$notification->id}")
        ->assertNotFound();
});

/**
 * @return array{0: User, 1: User, 2: ProjectIdea, 3: Assignment}
 */
function notificationScenario(): array
{
    $professor = User::factory()->create();
    $professor->assignRole('profesor');

    $student = User::factory()->create();
    $student->assignRole('student');

    $beneficiary = User::factory()->create();
    $beneficiary->assignRole('beneficiario');

    $idea = ProjectIdea::factory()->create([
        'organizer_id' => $beneficiary->id,
        'title' => 'Sistema de biblioteca',
        'status' => 'in_development',
    ]);

    $assignment = Assignment::factory()->create([
        'project_idea_id' => $idea->id,
        'student_id' => $student->id,
        'status' => 'accepted',
    ]);

    return [$professor, $student, $idea, $assignment];
}
