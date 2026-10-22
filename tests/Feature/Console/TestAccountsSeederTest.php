<?php

use App\Models\User;
use Database\Seeders\TestAccountsSeeder;

test('test accounts seeder creates the four testing accounts with their roles', function () {
    $this->seed(TestAccountsSeeder::class);

    expect(User::count())->toBe(4)
        ->and(User::where('email', 'admin@admin.com')->first()->hasRole('admin'))->toBeTrue()
        ->and(User::where('email', 'estudiante@test.com')->first()->hasRole('student'))->toBeTrue()
        ->and(User::where('email', 'beneficiario@test.com')->first()->hasRole('beneficiario'))->toBeTrue()
        ->and(User::where('email', 'profesor@test.com')->first()->hasRole('profesor'))->toBeTrue();
});
