<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Storage::fake('public');

    foreach (['student', 'beneficiario', 'profesor', 'admin'] as $role) {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    }
});

test('un usuario puede subir una foto de perfil', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->patch('/profile', [
        'name' => $user->name,
        'email' => $user->email,
        'profile_photo' => UploadedFile::fake()->image('avatar.jpg', 200, 200),
    ])->assertRedirect('/profile')
        ->assertSessionHas('status', 'profile-updated');

    $user->refresh();

    expect($user->profile_photo_path)->not->toBeNull()
        ->and(Storage::disk('public')->exists($user->profile_photo_path))->toBeTrue()
        ->and($user->profilePhotoUrl())->toContain('profile-photos/');
});

test('cada rol puede subir su foto de perfil', function (string $role) {
    $user = User::factory()->create();
    $user->assignRole($role);

    $this->actingAs($user)->patch('/profile', [
        'name' => $user->name,
        'email' => $user->email,
        'profile_photo' => UploadedFile::fake()->image('avatar.png', 100, 100),
    ])->assertRedirect('/profile');

    expect($user->fresh()->profile_photo_path)->not->toBeNull();
})->with(['student', 'beneficiario', 'profesor', 'admin']);

test('subir una nueva foto borra la anterior', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->patch('/profile', [
        'name' => $user->name,
        'email' => $user->email,
        'profile_photo' => UploadedFile::fake()->image('primera.jpg'),
    ]);

    $oldPath = $user->fresh()->profile_photo_path;

    $this->actingAs($user)->patch('/profile', [
        'name' => $user->name,
        'email' => $user->email,
        'profile_photo' => UploadedFile::fake()->image('segunda.jpg'),
    ]);

    $newPath = $user->fresh()->profile_photo_path;

    expect($newPath)->not->toBe($oldPath)
        ->and(Storage::disk('public')->exists($oldPath))->toBeFalse()
        ->and(Storage::disk('public')->exists($newPath))->toBeTrue();
});

test('un usuario puede quitar su foto de perfil', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->patch('/profile', [
        'name' => $user->name,
        'email' => $user->email,
        'profile_photo' => UploadedFile::fake()->image('avatar.jpg'),
    ]);

    $path = $user->fresh()->profile_photo_path;

    $this->actingAs($user)->patch('/profile', [
        'name' => $user->name,
        'email' => $user->email,
        'remove_profile_photo' => '1',
    ])->assertRedirect('/profile');

    expect($user->fresh()->profile_photo_path)->toBeNull()
        ->and(Storage::disk('public')->exists($path))->toBeFalse();
});

test('el archivo de la foto debe ser una imagen valida', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->patch('/profile', [
        'name' => $user->name,
        'email' => $user->email,
        'profile_photo' => UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf'),
    ])->assertSessionHasErrors('profile_photo');

    expect($user->fresh()->profile_photo_path)->toBeNull();
});

test('el formulario de perfil usa un control de archivo en español', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/profile')
        ->assertOk()
        ->assertSee('Foto de perfil')
        ->assertSee('Subir foto')
        ->assertSee('Ningún archivo seleccionado')
        ->assertDontSee('Choose File');
});

test('la foto de perfil se muestra como avatar en la navegacion', function () {
    $user = User::factory()->create();
    $user->assignRole('student');

    $this->actingAs($user)->patch('/profile', [
        'name' => $user->name,
        'email' => $user->email,
        'profile_photo' => UploadedFile::fake()->image('avatar.jpg'),
    ]);

    $this->actingAs($user)->get('/dashboard')
        ->assertOk()
        ->assertSee('profile-photos/', false);
});
