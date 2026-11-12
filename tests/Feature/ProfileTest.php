<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('el perfil muestra las secciones en español', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/profile')
        ->assertOk()
        ->assertSee('Mi perfil')
        ->assertSee('Datos personales')
        ->assertSee('Cambiar contraseña')
        ->assertSee('Eliminar cuenta')
        ->assertSee('Guardar cambios');
});

test('el perfil del alumno muestra su año de la carrera', function () {
    Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);

    $user = User::factory()->create(['career_year' => 3]);
    $user->assignRole('student');

    $this->actingAs($user)
        ->get('/profile')
        ->assertOk()
        ->assertSee('Año de la carrera')
        ->assertSee('3° año');
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('el beneficiario puede corregir el nombre de su institucion desde el perfil', function () {
    Role::firstOrCreate(['name' => 'beneficiario', 'guard_name' => 'web']);

    $user = User::factory()->create(['institution_name' => 'Institución anterior']);
    $user->assignRole('beneficiario');

    $this->actingAs($user)
        ->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'institution_name' => 'Institución corregida',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    expect($user->refresh()->institution_name)->toBe('Institución corregida');
});

test('el perfil del beneficiario muestra el campo institucion', function () {
    Role::firstOrCreate(['name' => 'beneficiario', 'guard_name' => 'web']);

    $user = User::factory()->create(['institution_name' => 'Institución original']);
    $user->assignRole('beneficiario');

    $this->actingAs($user)
        ->get('/profile')
        ->assertOk()
        ->assertSee('Institución u organización')
        ->assertSee('Institución original');
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});
