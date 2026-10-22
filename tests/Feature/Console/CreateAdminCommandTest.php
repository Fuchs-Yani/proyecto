<?php

use App\Models\User;

test('app:create-admin creates an admin user without demo data', function () {
    $this->artisan('app:create-admin', [
        '--name' => 'Admin Real',
        '--email' => 'admin@real.test',
        '--password' => 'password123',
    ])->assertSuccessful();

    $user = User::where('email', 'admin@real.test')->first();

    expect($user)->not->toBeNull()
        ->and($user->hasRole('admin'))->toBeTrue()
        ->and(User::count())->toBe(1);
});

test('app:create-admin rejects a duplicated email', function () {
    User::factory()->create(['email' => 'admin@real.test']);

    $this->artisan('app:create-admin', [
        '--name' => 'Admin Real',
        '--email' => 'admin@real.test',
        '--password' => 'password123',
    ])->assertFailed();

    expect(User::where('email', 'admin@real.test')->count())->toBe(1);
});
