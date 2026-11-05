<?php

use App\Models\Assignment;
use App\Models\ProjectIdea;
use App\Models\ProjectUpdate;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Storage::fake('public');

    $permissions = [
        'ViewAny:ProjectIdea', 'View:ProjectIdea',
        'ViewAny:Assignment', 'View:Assignment', 'Create:Assignment', 'Update:Assignment',
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

    $this->beneficiary = User::factory()->create(['name' => 'Beneficiaria Uno']);
    $this->beneficiary->assignRole('beneficiario');

    $this->idea = ProjectIdea::factory()->create([
        'organizer_id' => $this->beneficiary->id,
        'title' => 'Sistema de biblioteca escolar',
    ]);

    $this->student = User::factory()->create(['name' => 'Camila Rojas']);
    $this->student->assignRole('student');

    $this->assignment = Assignment::factory()->create([
        'project_idea_id' => $this->idea->id,
        'student_id' => $this->student->id,
        'status' => 'accepted',
    ]);

    $this->professor = User::factory()->create(['name' => 'Profesor Revisor']);
    $this->professor->assignRole('profesor');
});

function createProjectUpdate(Assignment $assignment, array $attributes = []): ProjectUpdate
{
    return ProjectUpdate::create(array_merge([
        'assignment_id' => $assignment->id,
        'project_idea_id' => $assignment->project_idea_id,
        'student_id' => $assignment->student_id,
        'description' => 'Avancé con el módulo de préstamos y las pruebas.',
        'status' => ProjectUpdate::STATUS_PENDING,
    ], $attributes));
}

test('el alumno postulado puede enviar un avance con descripcion, imagenes y repositorio', function () {
    $this->actingAs($this->student)->post("/assignments/{$this->assignment->id}/avances", [
        'description' => 'Terminé el alta de libros y el listado principal.',
        'repository_url' => 'https://github.com/camila/biblioteca',
        'images' => [
            UploadedFile::fake()->image('pantalla1.png', 800, 600),
            UploadedFile::fake()->image('pantalla2.jpg', 800, 600),
        ],
    ])->assertRedirect()
        ->assertSessionHas('status', 'Avance enviado. Queda pendiente de aprobación del profesor.');

    $update = ProjectUpdate::first();

    expect($update)->not->toBeNull()
        ->and($update->status)->toBe(ProjectUpdate::STATUS_PENDING)
        ->and($update->description)->toContain('alta de libros')
        ->and($update->repository_url)->toBe('https://github.com/camila/biblioteca')
        ->and($update->images)->toHaveCount(2)
        ->and(Storage::disk('public')->exists($update->images->first()->file_path))->toBeTrue();
});

test('un alumno que no es el postulado no puede enviar avances', function () {
    $other = User::factory()->create();
    $other->assignRole('student');

    $this->actingAs($other)->post("/assignments/{$this->assignment->id}/avances", [
        'description' => 'Intento enviar un avance ajeno.',
    ])->assertForbidden();

    expect(ProjectUpdate::count())->toBe(0);
});

test('un avance no se puede enviar sin descripcion ni con una URL invalida', function () {
    $this->actingAs($this->student)->post("/assignments/{$this->assignment->id}/avances", [
        'repository_url' => 'no-es-una-url',
    ])->assertSessionHasErrors(['description', 'repository_url']);

    expect(ProjectUpdate::count())->toBe(0);
});

test('el avance queda pendiente y el beneficiario no lo ve hasta que se apruebe', function () {
    $update = createProjectUpdate($this->assignment);

    $this->actingAs($this->beneficiary)->get("/ideas/{$this->idea->id}")
        ->assertOk()
        ->assertDontSee($update->description)
        ->assertDontSee('Avances del proyecto');
});

test('el profesor ve los avances agrupados por proyecto y puede aprobarlos', function () {
    $update = createProjectUpdate($this->assignment);

    $this->actingAs($this->professor)->get('/avances')
        ->assertOk()
        ->assertSee('Avances')
        ->assertSee('Sistema de biblioteca escolar')
        ->assertSee('Camila Rojas')
        ->assertSee('Pendiente')
        ->assertSee('Aprobar avance');

    $this->actingAs($this->professor)
        ->patch("/avances/{$update->id}/aprobar")
        ->assertRedirect()
        ->assertSessionHas('status', 'Avance aprobado. El beneficiario ya puede verlo.');

    $update->refresh();

    expect($update->status)->toBe(ProjectUpdate::STATUS_APPROVED)
        ->and($update->approved_at)->not->toBeNull()
        ->and($update->approved_by)->toBe($this->professor->id);
});

test('el beneficiario ve la descripcion y las imagenes aprobadas pero no el repositorio', function () {
    $update = createProjectUpdate($this->assignment, [
        'description' => 'Quedó funcionando el registro de préstamos.',
        'repository_url' => 'https://github.com/camila/biblioteca',
        'status' => ProjectUpdate::STATUS_APPROVED,
        'approved_at' => now(),
        'approved_by' => $this->professor->id,
    ]);

    $imagePath = UploadedFile::fake()->image('captura.png')->store('progress-images', 'public');
    $update->images()->create([
        'file_path' => $imagePath,
        'original_name' => 'captura.png',
        'mime_type' => 'image/png',
        'size' => 1024,
    ]);

    $this->actingAs($this->beneficiary)->get("/ideas/{$this->idea->id}")
        ->assertOk()
        ->assertSee('Avances del proyecto')
        ->assertSee('Quedó funcionando el registro de préstamos.')
        ->assertSee('progress-images/', false)
        ->assertDontSee('https://github.com/camila/biblioteca');
});

test('solo el profesor o el admin pueden aprobar un avance', function () {
    $update = createProjectUpdate($this->assignment);

    $this->actingAs($this->student)
        ->patch("/avances/{$update->id}/aprobar")
        ->assertForbidden();

    $this->actingAs($this->beneficiary)
        ->patch("/avances/{$update->id}/aprobar")
        ->assertForbidden();

    expect($update->fresh()->status)->toBe(ProjectUpdate::STATUS_PENDING);
});

test('el alumno ve sus propios avances con su estado en la idea', function () {
    createProjectUpdate($this->assignment, ['description' => 'Primera entrega del login.']);

    $this->actingAs($this->student)->get("/ideas/{$this->idea->id}")
        ->assertOk()
        ->assertSee('Agregar avance')
        ->assertSee('Mis avances')
        ->assertSee('Primera entrega del login.')
        ->assertSee('Pendiente');
});

test('la barra lateral del profesor incluye la seccion avances', function () {
    $this->actingAs($this->professor)->get('/dashboard')
        ->assertOk()
        ->assertSee('Avances')
        ->assertSee(route('progress.index'), false);
});

test('un alumno de una postulacion rechazada no puede enviar avances', function () {
    $this->assignment->update(['status' => 'rejected']);

    $this->actingAs($this->student)->post("/assignments/{$this->assignment->id}/avances", [
        'description' => 'Intento enviar un avance.',
    ])->assertForbidden();
});
