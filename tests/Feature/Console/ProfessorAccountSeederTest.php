<?php

use App\Models\User;
use Database\Seeders\ProfessorAccountSeeder;

test('professor account seeder creates an additional professor', function () {
    $this->seed(ProfessorAccountSeeder::class);

    $professor = User::where('email', 'ricardo.salas@test.com')->first();

    expect(User::role('profesor')->count())->toBe(1)
        ->and($professor)->not->toBeNull()
        ->and($professor->hasRole('profesor'))->toBeTrue();
});
