<?php

use App\Models\ProjectIdea;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('el dashboard del estudiante muestra directamente las ideas publicadas', function () {
    $permissions = [
        'ViewAny:ProjectIdea',
        'View:ProjectIdea',
        'ViewAny:Assignment',
        'View:Assignment',
        'Create:Assignment',
    ];

    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web'])
        ->syncPermissions($permissions);

    $student = User::factory()->create();
    $student->assignRole('student');

    ProjectIdea::factory()->create([
        'title' => 'Idea publicada para estudiantes',
        'status' => 'available',
    ]);

    $this->actingAs($student)->get('/dashboard')
        ->assertOk()
        ->assertSee('Dashboard')
        ->assertSee('Ideas publicadas')
        ->assertSee('Idea publicada para estudiantes')
        ->assertSee('Postularme')
        ->assertSee('Estudiante')
        ->assertSee('Editar perfil')
        ->assertDontSee('Actualiza tus datos y tu contraseña.')
        ->assertDontSee('Lista y revisa el detalle de las ideas y proyectos.')
        ->assertDontSee('Revisa el estado de tus postulaciones')
        ->assertDontSee('Admin Filament');
});
