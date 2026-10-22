<?php

use App\Models\ProjectIdea;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

test('database seeder only creates roles and permissions', function () {
    $this->seed();

    expect(User::count())->toBe(0)
        ->and(ProjectIdea::count())->toBe(0)
        ->and(Schema::hasColumn('users', 'role'))->toBeFalse()
        ->and(Schema::hasColumn('users', 'is_admin'))->toBeFalse()
        ->and(Role::where('name', 'admin')->exists())->toBeTrue()
        ->and(Role::where('name', 'student')->exists())->toBeTrue()
        ->and(Role::where('name', 'beneficiario')->exists())->toBeTrue()
        ->and(Role::where('name', 'profesor')->exists())->toBeTrue();
});
