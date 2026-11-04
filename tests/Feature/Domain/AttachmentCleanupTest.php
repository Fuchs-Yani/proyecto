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
});

test('al eliminar un usuario se borran definitivamente los archivos de sus ideas', function () {
    $owner = User::factory()->create();
    $idea = ProjectIdea::factory()->create(['organizer_id' => $owner->id]);
    $attachment = Attachment::factory()->create(['project_idea_id' => $idea->id]);

    Storage::disk('public')->put($attachment->file_path, 'contenido');

    $owner->delete();

    expect(ProjectIdea::find($idea->id))->toBeNull()
        ->and(Attachment::find($attachment->id))->toBeNull();

    Storage::disk('public')->assertMissing($attachment->file_path);
});

test('si falla el guardado de la idea no quedan archivos huerfanos', function () {
    Permission::firstOrCreate(['name' => 'Create:ProjectIdea', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'beneficiario', 'guard_name' => 'web'])
        ->givePermissionTo('Create:ProjectIdea');

    $beneficiario = User::factory()->create();
    $beneficiario->assignRole('beneficiario');

    Attachment::creating(function (): void {
        throw new RuntimeException('Fallo simulado al guardar el adjunto.');
    });

    $file = UploadedFile::fake()->create('detalle.pdf', 100, 'application/pdf');

    $this->actingAs($beneficiario)->post('/ideas', [
        'title' => 'Idea que no se guarda',
        'description' => 'Descripción de la idea.',
        'attachments' => [$file],
    ])->assertServerError();

    expect(ProjectIdea::where('title', 'Idea que no se guarda')->exists())->toBeFalse()
        ->and(Storage::disk('public')->allFiles('attachments'))->toBeEmpty();
});

test('el comando de limpieza elimina los archivos huerfanos y conserva los referenciados', function () {
    $idea = ProjectIdea::factory()->create();
    $attachment = Attachment::factory()->create(['project_idea_id' => $idea->id]);

    Storage::disk('public')->put($attachment->file_path, 'contenido');
    Storage::disk('public')->put('attachments/huerfano.pdf', 'basura');

    $this->artisan('attachments:prune')->assertSuccessful();

    Storage::disk('public')->assertExists($attachment->file_path);
    Storage::disk('public')->assertMissing('attachments/huerfano.pdf');
});

test('el comando de limpieza respeta la opcion dry-run', function () {
    Storage::disk('public')->put('attachments/huerfano.pdf', 'basura');

    $this->artisan('attachments:prune', ['--dry-run' => true])->assertSuccessful();

    Storage::disk('public')->assertExists('attachments/huerfano.pdf');
});
