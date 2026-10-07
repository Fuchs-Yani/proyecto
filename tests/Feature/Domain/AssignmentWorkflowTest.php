<?php

use App\Models\Assignment;
use App\Models\ProjectIdea;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['ViewAny:Assignment', 'View:Assignment', 'Create:Assignment', 'Update:Assignment', 'ViewAny:ProjectIdea', 'View:ProjectIdea'] as $p) {
        Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
    }
    Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web'])
        ->syncPermissions(['ViewAny:Assignment', 'View:Assignment', 'Create:Assignment', 'ViewAny:ProjectIdea', 'View:ProjectIdea']);
    Role::firstOrCreate(['name' => 'beneficiario', 'guard_name' => 'web'])
        ->syncPermissions(['ViewAny:Assignment', 'View:Assignment', 'Update:Assignment']);
});

test('apply sets applied_at and status applied', function () {
    $org = User::factory()->create();
    $student = User::factory()->create();
    $student->assignRole('student');
    $idea = ProjectIdea::factory()->create(['organizer_id' => $org->id]);

    $this->actingAs($student)->post("/ideas/{$idea->id}/apply");

    $a = Assignment::where('student_id', $student->id)->first();
    expect($a)->not->toBeNull()
        ->and($a->status)->toBe('applied')
        ->and($a->applied_at)->not->toBeNull();
});

test('status transition sets started_at and finished_at', function () {
    $a = Assignment::factory()->create(['status' => 'accepted']);

    $a->update(['status' => 'in_progress', 'started_at' => now()]);
    expect($a->fresh()->started_at)->not->toBeNull();

    $a->update(['status' => 'completed', 'finished_at' => now()]);
    expect($a->fresh()->finished_at)->not->toBeNull();
});
