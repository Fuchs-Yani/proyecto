<?php

use App\Models\User;
use Database\Seeders\StudentAccountsSeeder;

test('student accounts seeder creates seven students with the student role', function () {
    $this->seed(StudentAccountsSeeder::class);

    expect(User::role('student')->count())->toBe(7)
        ->and(User::where('email', 'juan.perez@test.com')->first()->hasRole('student'))->toBeTrue()
        ->and(User::where('email', 'sofia.ramirez@test.com')->first()->hasRole('student'))->toBeTrue()
        ->and(User::where('email', 'mateo.alvarez@test.com')->first()->hasRole('student'))->toBeTrue()
        ->and(User::where('email', 'valentina.torres@test.com')->first()->hasRole('student'))->toBeTrue()
        ->and(User::where('email', 'nicolas.herrera@test.com')->first()->hasRole('student'))->toBeTrue()
        ->and(User::where('email', 'camila.rojas@test.com')->first()->hasRole('student'))->toBeTrue()
        ->and(User::where('email', 'lucas.medina@test.com')->first()->hasRole('student'))->toBeTrue();
});
