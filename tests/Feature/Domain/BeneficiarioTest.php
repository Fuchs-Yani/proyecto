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
    $perms = [
        'ViewAny:ProjectIdea', 'View:ProjectIdea', 'Create:ProjectIdea',
        'ViewAny:Assignment', 'View:Assignment', 'Update:Assignment',
    ];

    foreach ($perms as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => 'beneficiario', 'guard_name' => 'web'])
        ->syncPermissions($perms);

    Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web'])
        ->syncPermissions(['ViewAny:ProjectIdea', 'View:ProjectIdea', 'ViewAny:Assignment', 'View:Assignment']);
});

test('un beneficiario puede crear una idea', function () {
    $beneficiario = User::factory()->create();
    $beneficiario->assignRole('beneficiario');

    $this->actingAs($beneficiario)->get('/ideas/crear')
        ->assertOk()
        ->assertSee('Nueva idea')
        ->assertSee('Documentos e imágenes de apoyo')
        ->assertSee('Seleccionar archivos')
        ->assertSee('Ningún archivo seleccionado')
        ->assertSee('Contanos con tus palabras')
        ->assertDontSee('requisito');

    $this->actingAs($beneficiario)->post('/ideas', [
        'title' => 'Idea del beneficiario',
        'description' => 'Descripción de la necesidad.',
    ])->assertRedirect();

    $idea = ProjectIdea::where('title', 'Idea del beneficiario')->first();

    expect($idea)->not->toBeNull()
        ->and($idea->organizer_id)->toBe($beneficiario->id)
        ->and($idea->status)->toBe('available');
});

test('un beneficiario puede publicar una idea con documentos e imágenes', function () {
    Storage::fake('public');

    $beneficiario = User::factory()->create();
    $beneficiario->assignRole('beneficiario');

    $pdf = UploadedFile::fake()->create('detalle.pdf', 250, 'application/pdf');
    $image = UploadedFile::fake()->create('inspiracion.png', 250, 'image/png');

    $this->actingAs($beneficiario)->post('/ideas', [
        'title' => 'Idea con archivos de apoyo',
        'description' => 'Una descripción sencilla.',
        'attachments' => [$pdf, $image],
    ])->assertRedirect();

    $idea = ProjectIdea::where('title', 'Idea con archivos de apoyo')->firstOrFail();

    $fileNames = $idea->attachments->pluck('file_name')->all();

    expect($idea->attachments)->toHaveCount(2)
        ->and($fileNames)->toContain('detalle.pdf')
        ->and($fileNames)->toContain('inspiracion.png');

    foreach ($idea->attachments as $attachment) {
        Storage::disk('public')->assertExists($attachment->file_path);
    }
});

test('una idea no acepta documentos que no sean pdf o imágenes', function () {
    Storage::fake('public');

    $beneficiario = User::factory()->create();
    $beneficiario->assignRole('beneficiario');

    $docx = UploadedFile::fake()->create(
        'detalle.docx',
        100,
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    );

    $this->actingAs($beneficiario)->post('/ideas', [
        'title' => 'Idea con archivo no permitido',
        'description' => 'Una descripción sencilla.',
        'attachments' => [$docx],
    ])->assertSessionHasErrors('attachments.0');

    expect(ProjectIdea::where('title', 'Idea con archivo no permitido')->exists())->toBeFalse();
});

test('un estudiante no puede crear ideas', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    $this->actingAs($student)->get('/ideas/crear')->assertForbidden();
});

test('el beneficiario ve quienes se postulan solo a sus propias ideas', function () {
    $owner = User::factory()->create(['name' => 'Beneficiario Dueño']);
    $owner->assignRole('beneficiario');

    $other = User::factory()->create(['name' => 'Beneficiario Otro']);
    $other->assignRole('beneficiario');

    $student = User::factory()->create(['name' => 'Alumno Postulante']);

    $ownIdea = ProjectIdea::factory()->create(['organizer_id' => $owner->id, 'status' => 'available']);
    $otherIdea = ProjectIdea::factory()->create(['organizer_id' => $other->id, 'status' => 'available']);

    Assignment::factory()->create(['project_idea_id' => $ownIdea->id, 'student_id' => $student->id]);
    Assignment::factory()->create(['project_idea_id' => $otherIdea->id, 'student_id' => $student->id]);

    $this->actingAs($owner)->get("/ideas/{$ownIdea->id}")
        ->assertOk()
        ->assertSee('Alumno Postulante');

    $this->actingAs($owner)->get("/ideas/{$otherIdea->id}")
        ->assertOk()
        ->assertDontSee('Alumno Postulante');
});

test('el dashboard del beneficiario no muestra la barra secundaria de panel', function () {
    $beneficiario = User::factory()->create();
    $beneficiario->assignRole('beneficiario');

    $this->actingAs($beneficiario)->get('/dashboard')
        ->assertOk()
        ->assertDontSee('<header', false)
        ->assertDontSee('Nueva idea')
        ->assertSee('Mis ideas')
        ->assertDontSee('Banco de Ideas y Proyectos de Software')
        ->assertDontSee('Lista y revisa el detalle de las ideas y proyectos.')
        ->assertDontSee('Actualiza tus datos y tu contraseña.')
        ->assertDontSee('Todas las postulaciones');
});

test('el dashboard del beneficiario muestra el modulo de avances aprobados de sus ideas', function () {
    $beneficiario = User::factory()->create();
    $beneficiario->assignRole('beneficiario');

    $otroBeneficiario = User::factory()->create();
    $otroBeneficiario->assignRole('beneficiario');

    $student = User::factory()->create();
    $student->assignRole('student');

    $idea = ProjectIdea::factory()->create([
        'organizer_id' => $beneficiario->id,
        'title' => 'Mi proyecto con avances',
    ]);

    $otherIdea = ProjectIdea::factory()->create([
        'organizer_id' => $otroBeneficiario->id,
        'title' => 'Proyecto de otro beneficiario',
    ]);

    $assignment = Assignment::factory()->create([
        'project_idea_id' => $idea->id,
        'student_id' => $student->id,
        'status' => 'accepted',
    ]);

    $otherAssignment = Assignment::factory()->create([
        'project_idea_id' => $otherIdea->id,
        'student_id' => $student->id,
        'status' => 'accepted',
    ]);

    ProjectUpdate::create([
        'assignment_id' => $assignment->id,
        'project_idea_id' => $idea->id,
        'student_id' => $student->id,
        'description' => 'Avance aprobado visible',
        'status' => ProjectUpdate::STATUS_APPROVED,
        'approved_at' => now(),
    ]);

    ProjectUpdate::create([
        'assignment_id' => $assignment->id,
        'project_idea_id' => $idea->id,
        'student_id' => $student->id,
        'description' => 'Avance pendiente oculto',
        'status' => ProjectUpdate::STATUS_PENDING,
    ]);

    ProjectUpdate::create([
        'assignment_id' => $otherAssignment->id,
        'project_idea_id' => $otherIdea->id,
        'student_id' => $student->id,
        'description' => 'Avance de otro proyecto',
        'status' => ProjectUpdate::STATUS_APPROVED,
        'approved_at' => now(),
    ]);

    $this->actingAs($beneficiario)->get('/dashboard')
        ->assertOk()
        ->assertSee('Avances')
        ->assertSee('1 avance aprobado')
        ->assertSee('Mi proyecto con avances')
        ->assertSee('Avance aprobado visible')
        ->assertDontSee('Avance pendiente oculto')
        ->assertDontSee('Proyecto de otro beneficiario')
        ->assertDontSee('Avance de otro proyecto');
});

test('el dashboard del beneficiario muestra un mensaje cuando todavia no hay avances aprobados', function () {
    $beneficiario = User::factory()->create();
    $beneficiario->assignRole('beneficiario');

    $this->actingAs($beneficiario)->get('/dashboard')
        ->assertOk()
        ->assertSee('Avances')
        ->assertSee('0 avances aprobados')
        ->assertSee('Todavía no hay avances aprobados en tus proyectos.');
});

test('el beneficiario ve solo sus propias ideas en mis ideas', function () {
    $beneficiario = User::factory()->create();
    $beneficiario->assignRole('beneficiario');

    $otroBeneficiario = User::factory()->create();
    $otroBeneficiario->assignRole('beneficiario');

    ProjectIdea::factory()->create([
        'organizer_id' => $beneficiario->id,
        'title' => 'Idea propia del beneficiario',
    ]);
    ProjectIdea::factory()->create([
        'organizer_id' => $otroBeneficiario->id,
        'title' => 'Idea de otro beneficiario',
    ]);

    $this->actingAs($beneficiario)->get('/mis-ideas')
        ->assertOk()
        ->assertSee('Mis ideas')
        ->assertSee('Idea propia del beneficiario')
        ->assertDontSee('Idea de otro beneficiario');
});

test('el beneficiario ve la barra de avances por etapas en mis ideas', function () {
    $beneficiario = User::factory()->create();
    $beneficiario->assignRole('beneficiario');

    $student = User::factory()->create();
    $student->assignRole('student');

    $idea = ProjectIdea::factory()->create([
        'organizer_id' => $beneficiario->id,
        'title' => 'Proyecto con etapas aprobadas',
        'status' => 'in_development',
    ]);

    $assignment = Assignment::factory()->create([
        'project_idea_id' => $idea->id,
        'student_id' => $student->id,
        'status' => 'accepted',
    ]);

    foreach ([
        [ProjectUpdate::STAGE_ANALYSIS, 'Análisis aprobado.', ProjectUpdate::STATUS_APPROVED, now()->subDays(3)],
        [ProjectUpdate::STAGE_DESIGN, 'Diseño aprobado.', ProjectUpdate::STATUS_APPROVED, now()->subDays(2)],
        [ProjectUpdate::STAGE_DEVELOPMENT, 'Desarrollo pendiente.', ProjectUpdate::STATUS_PENDING, null],
        [ProjectUpdate::STAGE_TESTING, 'Pruebas rechazadas.', ProjectUpdate::STATUS_REJECTED, null],
    ] as [$stage, $description, $status, $approvedAt]) {
        ProjectUpdate::create([
            'assignment_id' => $assignment->id,
            'project_idea_id' => $idea->id,
            'student_id' => $student->id,
            'description' => $description,
            'stage' => $stage,
            'status' => $status,
            'approved_at' => $approvedAt,
            'approved_by' => $approvedAt ? $beneficiario->id : null,
        ]);
    }

    $this->actingAs($beneficiario)->get('/mis-ideas')
        ->assertOk()
        ->assertSee('Proyecto con etapas aprobadas')
        ->assertSee('Progreso del proyecto')
        ->assertSee('Etapas aprobadas: 2 de 5')
        ->assertSee('40%')
        ->assertSee('Avances aprobados: 2')
        ->assertSee('Última etapa aprobada: Diseño');
});

test('el beneficiario no ve avances pendientes ni rechazados en la barra de mis ideas', function () {
    $beneficiario = User::factory()->create();
    $beneficiario->assignRole('beneficiario');

    $student = User::factory()->create();
    $student->assignRole('student');

    $idea = ProjectIdea::factory()->create([
        'organizer_id' => $beneficiario->id,
        'title' => 'Proyecto sin avances aprobados',
        'status' => 'in_development',
    ]);

    $assignment = Assignment::factory()->create([
        'project_idea_id' => $idea->id,
        'student_id' => $student->id,
        'status' => 'accepted',
    ]);

    ProjectUpdate::create([
        'assignment_id' => $assignment->id,
        'project_idea_id' => $idea->id,
        'student_id' => $student->id,
        'description' => 'Avance pendiente oculto',
        'stage' => ProjectUpdate::STAGE_ANALYSIS,
        'status' => ProjectUpdate::STATUS_PENDING,
    ]);

    ProjectUpdate::create([
        'assignment_id' => $assignment->id,
        'project_idea_id' => $idea->id,
        'student_id' => $student->id,
        'description' => 'Avance rechazado oculto',
        'stage' => ProjectUpdate::STAGE_DESIGN,
        'status' => ProjectUpdate::STATUS_REJECTED,
    ]);

    $this->actingAs($beneficiario)->get('/mis-ideas')
        ->assertOk()
        ->assertSee('Progreso del proyecto')
        ->assertSee('Etapas aprobadas: 0 de 5')
        ->assertSee('0%')
        ->assertSee('Avances aprobados: 0')
        ->assertDontSee('Última etapa aprobada');
});

test('un estudiante no puede acceder a mis ideas', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    $this->actingAs($student)->get('/mis-ideas')->assertForbidden();
});

test('un beneficiario que ya publico una idea no puede abrir el formulario de nueva idea', function () {
    $beneficiario = User::factory()->create();
    $beneficiario->assignRole('beneficiario');

    ProjectIdea::factory()->create(['organizer_id' => $beneficiario->id]);

    $this->actingAs($beneficiario)
        ->get('/ideas/crear')
        ->assertRedirect('/mis-ideas')
        ->assertSessionHas('status', 'Ya publicaste tu idea. Solo se permite una idea por beneficiario.');
});

test('un beneficiario que ya publico una idea no puede crear otra', function () {
    $beneficiario = User::factory()->create();
    $beneficiario->assignRole('beneficiario');

    ProjectIdea::factory()->create(['organizer_id' => $beneficiario->id]);

    $this->actingAs($beneficiario)->post('/ideas', [
        'title' => 'Segunda idea no permitida',
        'description' => 'No debería guardarse.',
    ])->assertRedirect('/mis-ideas');

    expect(ProjectIdea::where('title', 'Segunda idea no permitida')->exists())->toBeFalse()
        ->and($beneficiario->projectIdeas()->count())->toBe(1);
});

test('el boton nueva idea desaparece cuando el beneficiario ya tiene su idea', function () {
    $beneficiario = User::factory()->create();
    $beneficiario->assignRole('beneficiario');

    ProjectIdea::factory()->create(['organizer_id' => $beneficiario->id]);

    $this->actingAs($beneficiario)->get('/mis-ideas')
        ->assertOk()
        ->assertSee('Mis ideas')
        ->assertDontSee('Nueva idea');
});

test('un administrador no tiene el limite de una idea', function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'])
        ->syncPermissions(['ViewAny:ProjectIdea', 'View:ProjectIdea', 'Create:ProjectIdea']);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    ProjectIdea::factory()->create(['organizer_id' => $admin->id]);

    $this->actingAs($admin)
        ->get('/ideas/crear')
        ->assertOk();
});
