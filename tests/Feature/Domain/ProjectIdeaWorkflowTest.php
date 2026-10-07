<?php

use App\Models\ProjectIdea;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $perms = [
        'ViewAny:ProjectIdea', 'View:ProjectIdea', 'Create:ProjectIdea', 'Update:ProjectIdea',
        'ViewAny:Assignment', 'View:Assignment', 'Create:Assignment', 'Update:Assignment',
        'ViewAny:Attachment', 'View:Attachment', 'Create:Attachment',
    ];
    foreach ($perms as $p) {
        Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
    }
    Role::firstOrCreate(['name' => 'beneficiario', 'guard_name' => 'web'])
        ->syncPermissions(['ViewAny:ProjectIdea', 'View:ProjectIdea', 'Create:ProjectIdea', 'Update:ProjectIdea']);
    Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web'])
        ->syncPermissions(['ViewAny:ProjectIdea', 'View:ProjectIdea', 'ViewAny:Assignment', 'View:Assignment', 'Create:Assignment']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'])
        ->syncPermissions(Permission::all());
});

test('beneficiario can list available ideas', function () {
    $org = User::factory()->create();
    $org->assignRole('beneficiario');
    ProjectIdea::factory()->create(['organizer_id' => $org->id, 'status' => 'available']);

    $this->actingAs($org)->get('/ideas')->assertOk();
});

test('student can apply once to an idea', function () {
    $org = User::factory()->create();
    $org->assignRole('beneficiario');
    $student = User::factory()->create();
    $student->assignRole('student');
    $idea = ProjectIdea::factory()->create(['organizer_id' => $org->id, 'status' => 'available']);

    $this->actingAs($student)->post("/ideas/{$idea->id}/apply")->assertRedirect();
    expect($idea->assignments()->where('student_id', $student->id)->count())->toBe(1);

    // segundo intento no duplica
    $this->actingAs($student)->post("/ideas/{$idea->id}/apply")->assertRedirect();
    expect($idea->assignments()->where('student_id', $student->id)->count())->toBe(1);
});
