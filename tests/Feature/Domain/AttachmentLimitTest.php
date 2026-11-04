<?php

use App\Models\Attachment;
use App\Models\ProjectIdea;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Storage::fake('public');

    $permissions = [
        'ViewAny:ProjectIdea', 'View:ProjectIdea', 'Create:ProjectIdea', 'Update:ProjectIdea',
        'ViewAny:Attachment', 'View:Attachment', 'Create:Attachment', 'Delete:Attachment',
    ];

    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => 'beneficiario', 'guard_name' => 'web'])
        ->syncPermissions($permissions);
});

test('al crear una idea no se permiten mas de dos archivos', function () {
    $beneficiario = User::factory()->create();
    $beneficiario->assignRole('beneficiario');

    $files = [
        UploadedFile::fake()->create('uno.pdf', 100, 'application/pdf'),
        UploadedFile::fake()->create('dos.png', 100, 'image/png'),
        UploadedFile::fake()->create('tres.pdf', 100, 'application/pdf'),
    ];

    $this->actingAs($beneficiario)->post('/ideas', [
        'title' => 'Idea con demasiados archivos',
        'description' => 'No debería guardarse.',
        'attachments' => $files,
    ])->assertSessionHasErrors('attachments');

    expect(ProjectIdea::where('title', 'Idea con demasiados archivos')->exists())->toBeFalse()
        ->and(Attachment::count())->toBe(0)
        ->and(Storage::disk('public')->allFiles('attachments'))->toBeEmpty();
});

test('se puede subir un segundo archivo a una idea', function () {
    $beneficiario = User::factory()->create();
    $beneficiario->assignRole('beneficiario');

    $idea = ProjectIdea::factory()->create(['organizer_id' => $beneficiario->id]);
    Attachment::factory()->create(['project_idea_id' => $idea->id]);

    $file = UploadedFile::fake()->create('segundo.pdf', 100, 'application/pdf');

    $this->actingAs($beneficiario)
        ->post("/ideas/{$idea->id}/attachments", ['file' => $file])
        ->assertRedirect();

    expect($idea->attachments()->count())->toBe(2);
});

test('no se puede subir un tercer archivo a una idea', function () {
    $beneficiario = User::factory()->create();
    $beneficiario->assignRole('beneficiario');

    $idea = ProjectIdea::factory()->create(['organizer_id' => $beneficiario->id]);
    Attachment::factory()->count(2)->create(['project_idea_id' => $idea->id]);

    $file = UploadedFile::fake()->create('tercero.pdf', 100, 'application/pdf');

    $this->actingAs($beneficiario)
        ->post("/ideas/{$idea->id}/attachments", ['file' => $file])
        ->assertSessionHasErrors('file');

    expect($idea->attachments()->count())->toBe(2)
        ->and(Storage::disk('public')->allFiles('attachments'))->toBeEmpty();
});

test('el modelo impide superar el limite de adjuntos por idea', function () {
    $idea = ProjectIdea::factory()->create();
    Attachment::factory()->count(ProjectIdea::MAX_ATTACHMENTS)->create(['project_idea_id' => $idea->id]);

    expect(fn () => Attachment::factory()->create(['project_idea_id' => $idea->id]))
        ->toThrow(ValidationException::class);
});

test('la idea muestra el limite alcanzado y oculta el formulario de subida', function () {
    $beneficiario = User::factory()->create();
    $beneficiario->assignRole('beneficiario');

    $idea = ProjectIdea::factory()->create(['organizer_id' => $beneficiario->id]);
    Attachment::factory()->count(ProjectIdea::MAX_ATTACHMENTS)->create(['project_idea_id' => $idea->id]);

    $this->actingAs($beneficiario)
        ->get("/ideas/{$idea->id}")
        ->assertOk()
        ->assertSee('Editar idea')
        ->assertSee('Adjuntos (2/2)')
        ->assertSee('Alcanzaste el máximo de 2 archivos por idea.')
        ->assertDontSee('Subir archivo');
});
