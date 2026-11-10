<?php

use App\Models\User;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('registration screen shows the career year options for students', function () {
    $this->get('/register')
        ->assertOk()
        ->assertSee('Año de la carrera')
        ->assertSee('2° año')
        ->assertSee('3° año');
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'student',
        'career_year' => 2,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'test@example.com')->first();
    expect($user->hasRole('student'))->toBeTrue()
        ->and($user->career_year)->toBe(2);
});

test('a student must indicate a valid career year when registering', function () {
    $base = [
        'name' => 'Alumno Sin Año',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'student',
    ];

    $this->post('/register', $base + ['email' => 'sin-anio@example.com'])
        ->assertSessionHasErrors('career_year');

    $this->post('/register', $base + [
        'email' => 'anio-invalido@example.com',
        'career_year' => 4,
    ])->assertSessionHasErrors('career_year');

    expect(User::whereIn('email', ['sin-anio@example.com', 'anio-invalido@example.com'])->exists())->toBeFalse();
});

test('a non student does not need to indicate a career year', function () {
    $this->post('/register', [
        'name' => 'Profesor de Prueba',
        'email' => 'profesor-registro@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'profesor',
    ])->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'profesor-registro@example.com')->first();

    expect($user->hasRole('profesor'))->toBeTrue()
        ->and($user->career_year)->toBeNull();
});
