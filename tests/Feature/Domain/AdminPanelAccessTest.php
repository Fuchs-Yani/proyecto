<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('solo el administrador puede acceder al panel', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->get('/admin')->assertOk();
    $this->actingAs($admin)->get('/admin/shield/roles')->assertOk();
});

test('los demas roles no pueden acceder al panel', function (string $role) {
    $user = User::factory()->create();
    $user->assignRole($role);

    $this->actingAs($user)->get('/admin')->assertForbidden();
    $this->actingAs($user)->get('/admin/shield/roles')->assertForbidden();
})->with(['student', 'beneficiario', 'profesor']);

test('el rol super_admin no existe y su configuracion esta deshabilitada', function () {
    expect(Role::where('name', 'super_admin')->exists())->toBeFalse()
        ->and(config('filament-shield.super_admin.enabled'))->toBeFalse();
});
