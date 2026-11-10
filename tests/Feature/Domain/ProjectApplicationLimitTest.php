<?php

use App\Models\Assignment;
use App\Models\ProjectIdea;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $perms = [
        'ViewAny:ProjectIdea', 'View:ProjectIdea', 'Create:ProjectIdea', 'Update:ProjectIdea', 'Delete:ProjectIdea',
        'ViewAny:Assignment', 'View:Assignment', 'Create:Assignment', 'Update:Assignment', 'Delete:Assignment',
        'ViewAny:Attachment', 'View:Attachment',
    ];

    foreach ($perms as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => 'beneficiario', 'guard_name' => 'web'])
        ->syncPermissions([
            'ViewAny:ProjectIdea', 'View:ProjectIdea', 'Create:ProjectIdea', 'Update:ProjectIdea', 'Delete:ProjectIdea',
            'ViewAny:Assignment', 'View:Assignment', 'Update:Assignment',
            'ViewAny:Attachment', 'View:Attachment',
        ]);

    Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web'])
        ->syncPermissions([
            'ViewAny:ProjectIdea', 'View:ProjectIdea',
            'ViewAny:Assignment', 'View:Assignment', 'Create:Assignment', 'Delete:Assignment',
        ]);

    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());
});

function applicationLimitUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('el cupo maximo limita la cantidad de postulantes', function () {
    $owner = applicationLimitUser('beneficiario');
    $idea = ProjectIdea::factory()->create([
        'organizer_id' => $owner->id,
        'status' => 'available',
        'max_students' => 2,
    ]);

    foreach (range(1, 2) as $i) {
        $this->actingAs(applicationLimitUser('student'))
            ->post("/ideas/{$idea->id}/apply")
            ->assertSessionHas('status', 'Postulación creada.');
    }

    $this->actingAs(applicationLimitUser('student'))
        ->post("/ideas/{$idea->id}/apply")
        ->assertSessionHas('status', 'Esta idea ya alcanzó el cupo máximo de postulantes.');

    expect(Assignment::where('project_idea_id', $idea->id)->count())->toBe(2);
});

test('sin cupo definido por el profesor no se puede postular', function () {
    $owner = applicationLimitUser('beneficiario');
    $idea = ProjectIdea::factory()->create([
        'organizer_id' => $owner->id,
        'status' => 'available',
        'max_students' => null,
    ]);

    $this->actingAs(applicationLimitUser('student'))
        ->post("/ideas/{$idea->id}/apply")
        ->assertSessionHas('status', 'El profesor todavía no definió el cupo para esta idea.');

    expect(Assignment::where('project_idea_id', $idea->id)->count())->toBe(0);
});

test('una postulacion rechazada libera un cupo', function () {
    $owner = applicationLimitUser('beneficiario');
    $idea = ProjectIdea::factory()->create([
        'organizer_id' => $owner->id,
        'status' => 'available',
        'max_students' => 2,
    ]);

    $first = assignmentForLimit($idea, applicationLimitUser('student'));
    $first->update(['status' => 'rejected']);

    $this->actingAs(applicationLimitUser('student'))
        ->post("/ideas/{$idea->id}/apply")
        ->assertSessionHas('status', 'Postulación creada.');

    expect(Assignment::where('project_idea_id', $idea->id)->count())->toBe(2);
});

test('retirar una postulacion libera un cupo', function () {
    $owner = applicationLimitUser('beneficiario');
    $idea = ProjectIdea::factory()->create([
        'organizer_id' => $owner->id,
        'status' => 'available',
        'max_students' => 2,
    ]);

    $student = applicationLimitUser('student');
    $assignment = assignmentForLimit($idea, $student);

    $this->actingAs($student)
        ->delete("/assignments/{$assignment->id}")
        ->assertRedirect();

    $this->actingAs(applicationLimitUser('student'))
        ->post("/ideas/{$idea->id}/apply")
        ->assertSessionHas('status', 'Postulación creada.');

    expect(Assignment::where('project_idea_id', $idea->id)->count())->toBe(1);
});

test('el detalle muestra el cupo y bloquea el boton cuando esta lleno', function () {
    $owner = applicationLimitUser('beneficiario');
    $idea = ProjectIdea::factory()->create([
        'organizer_id' => $owner->id,
        'status' => 'available',
        'max_students' => 2,
    ]);

    assignmentForLimit($idea, applicationLimitUser('student'));
    assignmentForLimit($idea, applicationLimitUser('student'));

    $this->actingAs(applicationLimitUser('student'))
        ->get("/ideas/{$idea->id}")
        ->assertOk()
        ->assertSee('Cupo de postulantes: 2 / 2')
        ->assertSee('Cupo completo')
        ->assertSee('Esta idea ya alcanzó el cupo máximo de postulantes.')
        ->assertDontSee('Postularme');
});

test('el estudiante que ya se postulo ve su estado aunque el cupo este lleno', function () {
    $owner = applicationLimitUser('beneficiario');
    $idea = ProjectIdea::factory()->create([
        'organizer_id' => $owner->id,
        'status' => 'available',
        'max_students' => 2,
    ]);

    $student = applicationLimitUser('student');
    assignmentForLimit($idea, $student);
    assignmentForLimit($idea, applicationLimitUser('student'));

    $this->actingAs($student)
        ->get("/ideas/{$idea->id}")
        ->assertOk()
        ->assertSee('Ya postulado.')
        ->assertDontSee('Esta idea ya alcanzó el cupo máximo de postulantes.');
});

test('el listado oculta el boton postularme cuando el cupo esta lleno', function () {
    $owner = applicationLimitUser('beneficiario');
    $idea = ProjectIdea::factory()->create([
        'organizer_id' => $owner->id,
        'status' => 'available',
        'max_students' => 2,
        'title' => 'Proyecto con cupo lleno',
    ]);

    assignmentForLimit($idea, applicationLimitUser('student'));
    assignmentForLimit($idea, applicationLimitUser('student'));

    $this->actingAs(applicationLimitUser('student'))
        ->get('/ideas')
        ->assertOk()
        ->assertSee('Proyecto con cupo lleno')
        ->assertSee('2/2 postulantes')
        ->assertDontSee('Postularme');
});

test('el administrador puede editar el cupo desde el panel', function () {
    $admin = applicationLimitUser('admin');
    $idea = ProjectIdea::factory()->create(['max_students' => null]);

    $this->actingAs($admin)
        ->get("/admin/project-ideas/{$idea->id}/edit")
        ->assertOk()
        ->assertSee('Cantidad de alumnos');

    $idea->update(['max_students' => 4]);

    expect($idea->fresh()->max_students)->toBe(4);
});

test('un alumno no puede postularse a otra idea si ya tiene una postulacion activa', function () {
    $owner = applicationLimitUser('beneficiario');
    $student = applicationLimitUser('student');

    $firstIdea = ProjectIdea::factory()->create([
        'organizer_id' => $owner->id,
        'title' => 'Primera idea del alumno',
        'status' => 'available',
        'max_students' => 2,
    ]);

    $secondIdea = ProjectIdea::factory()->create([
        'organizer_id' => $owner->id,
        'title' => 'Segunda idea del alumno',
        'status' => 'available',
        'max_students' => 2,
    ]);

    $this->actingAs($student)->post("/ideas/{$firstIdea->id}/apply")
        ->assertSessionHas('status', 'Postulación creada.');

    $this->actingAs($student)->post("/ideas/{$secondIdea->id}/apply")
        ->assertSessionHas('status', 'Ya estás postulado a otra idea. Solo podés postularte a una.');

    expect(Assignment::where('student_id', $student->id)->count())->toBe(1)
        ->and(Assignment::where('student_id', $student->id)->where('project_idea_id', $secondIdea->id)->exists())->toBeFalse();
});

test('el detalle de otra idea avisa que el alumno ya esta postulado y oculta el boton', function () {
    $owner = applicationLimitUser('beneficiario');
    $student = applicationLimitUser('student');

    $firstIdea = ProjectIdea::factory()->create([
        'organizer_id' => $owner->id,
        'title' => 'Proyecto ya elegido',
        'status' => 'available',
        'max_students' => 2,
    ]);

    $secondIdea = ProjectIdea::factory()->create([
        'organizer_id' => $owner->id,
        'title' => 'Proyecto solo para ver',
        'status' => 'available',
        'max_students' => 2,
    ]);

    assignmentForLimit($firstIdea, $student);

    $this->actingAs($student)->get("/ideas/{$secondIdea->id}")
        ->assertOk()
        ->assertSee('Proyecto solo para ver')
        ->assertSee('Ya estás postulado a')
        ->assertSee('Proyecto ya elegido')
        ->assertSee('Solo podés postularte a una idea, pero podés seguir viendo las demás.')
        ->assertDontSee('Postularme');
});

test('el listado de ideas muestra ya postulado y oculta el boton en las demas', function () {
    $owner = applicationLimitUser('beneficiario');
    $student = applicationLimitUser('student');

    $firstIdea = ProjectIdea::factory()->create([
        'organizer_id' => $owner->id,
        'title' => 'Idea elegida en el listado',
        'status' => 'available',
        'max_students' => 2,
    ]);

    ProjectIdea::factory()->create([
        'organizer_id' => $owner->id,
        'title' => 'Otra idea visible en el listado',
        'status' => 'available',
        'max_students' => 2,
    ]);

    assignmentForLimit($firstIdea, $student);

    $this->actingAs($student)->get('/ideas')
        ->assertOk()
        ->assertSee('Idea elegida en el listado')
        ->assertSee('Otra idea visible en el listado')
        ->assertSee('Ya postulado')
        ->assertDontSee('Postularme');
});

test('una postulacion rechazada no bloquea postularse a otra idea', function () {
    $owner = applicationLimitUser('beneficiario');
    $student = applicationLimitUser('student');

    $firstIdea = ProjectIdea::factory()->create([
        'organizer_id' => $owner->id,
        'status' => 'available',
        'max_students' => 2,
    ]);

    $secondIdea = ProjectIdea::factory()->create([
        'organizer_id' => $owner->id,
        'status' => 'available',
        'max_students' => 2,
    ]);

    assignmentForLimit($firstIdea, $student)->update(['status' => 'rejected']);

    $this->actingAs($student)->post("/ideas/{$secondIdea->id}/apply")
        ->assertSessionHas('status', 'Postulación creada.');

    expect(Assignment::where('student_id', $student->id)->count())->toBe(2)
        ->and(Assignment::where('student_id', $student->id)->where('project_idea_id', $secondIdea->id)->exists())->toBeTrue();
});

function assignmentForLimit(ProjectIdea $idea, User $student): Assignment
{
    return Assignment::create([
        'project_idea_id' => $idea->id,
        'student_id' => $student->id,
        'status' => 'applied',
        'applied_at' => now(),
    ]);
}
