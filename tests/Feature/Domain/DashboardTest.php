<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('el dashboard del estudiante muestra sus modulos y no el panel admin', function () {
    $permission = Permission::firstOrCreate(['name' => 'ViewAny:Assignment', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web'])->syncPermissions([$permission]);

    $student = User::factory()->create();
    $student->assignRole('student');

    $this->actingAs($student)->get('/dashboard')
        ->assertOk()
        ->assertSee('Ideas')
        ->assertSee('Postulaciones')
        ->assertSee('Editar perfil')
        ->assertDontSee('Admin Filament');
});
