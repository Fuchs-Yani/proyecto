<?php

use App\Models\Assignment;
use App\Models\ProjectIdea;
use App\Models\User;
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
        ->assertSee('Nueva idea');

    $this->actingAs($beneficiario)->post('/ideas', [
        'title' => 'Idea del beneficiario',
        'description' => 'Descripción de la necesidad.',
    ])->assertRedirect();

    $idea = ProjectIdea::where('title', 'Idea del beneficiario')->first();

    expect($idea)->not->toBeNull()
        ->and($idea->organizer_id)->toBe($beneficiario->id)
        ->and($idea->status)->toBe('available');
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

test('el dashboard del beneficiario muestra el modulo nueva idea', function () {
    $beneficiario = User::factory()->create();
    $beneficiario->assignRole('beneficiario');

    $this->actingAs($beneficiario)->get('/dashboard')
        ->assertOk()
        ->assertSee('Nueva idea');
});
