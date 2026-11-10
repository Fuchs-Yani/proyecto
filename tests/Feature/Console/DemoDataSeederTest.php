<?php

use App\Models\Assignment;
use App\Models\ProjectIdea;
use App\Models\ProjectUpdate;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

test('el escenario demo cubre todos los estados del sistema', function () {
    Storage::fake('public');

    $this->seed(DemoDataSeeder::class);

    expect(User::role('student')->where('career_year', 2)->count())->toBe(4)
        ->and(User::role('student')->where('career_year', 3)->count())->toBe(4)
        ->and(ProjectIdea::where('status', 'available')->count())->toBe(4)
        ->and(ProjectIdea::where('status', 'in_development')->count())->toBe(2)
        ->and(ProjectIdea::where('status', 'completed')->count())->toBe(2)
        ->and(ProjectIdea::where('status', 'cancelled')->count())->toBe(1);

    foreach (['applied', 'accepted', 'rejected', 'in_progress', 'completed', 'withdrawn'] as $status) {
        expect(Assignment::where('status', $status)->exists())->toBeTrue();
    }

    foreach (array_keys(ProjectUpdate::STAGES) as $stage) {
        expect(ProjectUpdate::where('stage', $stage)->exists())->toBeTrue();
    }

    expect(ProjectUpdate::where('status', 'pending')->exists())->toBeTrue()
        ->and(ProjectUpdate::where('status', 'rejected')->exists())->toBeTrue()
        ->and(ProjectUpdate::where('status', 'approved')->exists())->toBeTrue();
});

test('el escenario demo incluye una idea pendiente de cupo y el caso de inicio con pendientes', function () {
    Storage::fake('public');

    $this->seed(DemoDataSeeder::class);

    $pendingReview = ProjectIdea::where('title', 'Sistema de turnos para el centro de salud')->firstOrFail();
    $startCandidate = ProjectIdea::where('title', 'Sistema web para mostrar animalitos en adopción')->firstOrFail();

    expect($pendingReview->status)->toBe('available')
        ->and($pendingReview->max_students)->toBeNull()
        ->and($pendingReview->isPendingReview())->toBeTrue()
        // El cupo está completo entre 1 aceptado y 1 pendiente, así que el
        // profesor puede iniciar el proyecto con un solo integrante.
        ->and($startCandidate->status)->toBe('available')
        ->and($startCandidate->max_students)->toBe(2)
        ->and($startCandidate->activeAssignmentsCount())->toBe(2)
        ->and($startCandidate->acceptedAssignmentsCount())->toBe(1)
        ->and($startCandidate->pendingAssignmentsCount())->toBe(1);
});

test('los paneles principales renderizan con los datos demo', function () {
    Storage::fake('public');

    $this->seed(DemoDataSeeder::class);

    $student = User::where('email', 'estudiante@test.com')->firstOrFail();
    $professor = User::where('email', 'profesor@test.com')->firstOrFail();
    $beneficiary = User::where('email', 'escuela@test.com')->firstOrFail();

    $this->actingAs($student)->get('/dashboard')
        ->assertOk()
        ->assertSee('Ideas publicadas');

    $this->actingAs($student)->get('/assignments')->assertOk();

    $this->actingAs($professor)->get('/dashboard')
        ->assertOk()
        ->assertSee('Estado de los proyectos');

    $this->actingAs($professor)->get('/avances')
        ->assertOk()
        ->assertSee('Sistema de gestión para la biblioteca escolar');

    $this->actingAs($professor)->get('/assignments?status=applied')
        ->assertOk()
        ->assertSee('Postulados');

    $this->actingAs($beneficiary)->get('/dashboard')
        ->assertOk()
        ->assertSee('Avances');

    $this->actingAs($beneficiary)->get('/mis-ideas')
        ->assertOk()
        ->assertSee('Progreso del proyecto');
});

test('el database seeder carga todo el escenario demo con un solo comando', function () {
    Storage::fake('public');

    $this->seed();

    expect(User::where('email', 'profesor@test.com')->exists())->toBeTrue()
        ->and(User::where('email', 'estudiante@test.com')->exists())->toBeTrue()
        ->and(ProjectIdea::count())->toBe(9)
        ->and(Assignment::count())->toBe(11)
        ->and(ProjectUpdate::count())->toBe(20)
        ->and(Role::count())->toBe(4);
});

test('el escenario demo se puede volver a ejecutar sin duplicar datos', function () {
    Storage::fake('public');

    $this->seed(DemoDataSeeder::class);

    $counts = [
        ProjectIdea::count(),
        Assignment::count(),
        ProjectUpdate::count(),
    ];

    $this->seed(DemoDataSeeder::class);

    expect([
        ProjectIdea::count(),
        Assignment::count(),
        ProjectUpdate::count(),
    ])->toBe($counts)
        ->and(ProjectUpdate::where('stage', 'entrega')->count())->toBe(2)
        ->and(ProjectIdea::count())->toBe(9);
});
