<?php

use App\Models\Attachment;
use App\Models\ProjectIdea;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Storage::fake('public');
    foreach (['View:Attachment', 'ViewAny:Attachment', 'Create:Attachment', 'ViewAny:ProjectIdea', 'View:ProjectIdea'] as $p) {
        Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
    }
    Role::firstOrCreate(['name' => 'beneficiario', 'guard_name' => 'web'])
        ->syncPermissions(['View:Attachment', 'ViewAny:Attachment', 'Create:Attachment', 'ViewAny:ProjectIdea', 'View:ProjectIdea']);
});

test('beneficiario can upload attachment with validation', function () {
    $org = User::factory()->create();
    $org->assignRole('beneficiario');
    $idea = ProjectIdea::factory()->create(['organizer_id' => $org->id]);

    $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

    $this->actingAs($org)->post("/ideas/{$idea->id}/attachments", ['file' => $file])
        ->assertRedirect();

    expect(Attachment::where('project_idea_id', $idea->id)->count())->toBe(1);
});

test('upload rejects invalid mime', function () {
    $org = User::factory()->create();
    $org->assignRole('beneficiario');
    $idea = ProjectIdea::factory()->create(['organizer_id' => $org->id]);

    $file = UploadedFile::fake()->create('mal.exe', 100, 'application/x-msdownload');

    $this->actingAs($org)->post("/ideas/{$idea->id}/attachments", ['file' => $file])
        ->assertSessionHasErrors('file');
});

test('beneficiario puede subir imagenes', function () {
    $org = User::factory()->create();
    $org->assignRole('beneficiario');
    $idea = ProjectIdea::factory()->create(['organizer_id' => $org->id]);

    $file = UploadedFile::fake()->image('foto.jpg', 800, 600);

    $this->actingAs($org)->post("/ideas/{$idea->id}/attachments", ['file' => $file])
        ->assertRedirect();

    $attachment = Attachment::where('project_idea_id', $idea->id)->first();

    expect($attachment)->not->toBeNull()
        ->and($attachment->file_type)->toBe('jpg');

    Storage::disk('public')->assertExists($attachment->file_path);
});

test('beneficiario no puede subir archivos a ideas de otro beneficiario', function () {
    $owner = User::factory()->create();
    $owner->assignRole('beneficiario');

    $other = User::factory()->create();
    $other->assignRole('beneficiario');

    $idea = ProjectIdea::factory()->create(['organizer_id' => $owner->id]);
    $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

    $this->actingAs($other)->post("/ideas/{$idea->id}/attachments", ['file' => $file])
        ->assertForbidden();

    expect(Attachment::where('project_idea_id', $idea->id)->count())->toBe(0);
});
