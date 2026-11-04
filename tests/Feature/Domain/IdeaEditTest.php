<?php

use App\Models\ProjectIdea;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $permissions = [
        'ViewAny:ProjectIdea',
        'View:ProjectIdea',
        'Create:ProjectIdea',
        'Update:ProjectIdea',
        'Delete:ProjectIdea',
    ];

    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => 'beneficiario', 'guard_name' => 'web'])
        ->syncPermissions($permissions);

    $this->beneficiario = User::factory()->create();
    $this->beneficiario->assignRole('beneficiario');
});

test('el detalle muestra el boton editar idea con sus opciones', function () {
    $idea = ProjectIdea::factory()->create([
        'organizer_id' => $this->beneficiario->id,
        'title' => 'Sistema de biblioteca',
    ]);

    $this->actingAs($this->beneficiario)
        ->get("/ideas/{$idea->id}")
        ->assertOk()
        ->assertSee('Editar idea')
        ->assertSee('Descripción de la idea')
        ->assertSee('Adjuntos')
        ->assertSee('Etiquetas');
});

test('el dueño puede editar la descripcion de la idea', function () {
    $idea = ProjectIdea::factory()->create([
        'organizer_id' => $this->beneficiario->id,
        'description' => 'Descripción original.',
    ]);

    $this->actingAs($this->beneficiario)
        ->patch("/ideas/{$idea->id}", ['description' => 'Descripción actualizada por el dueño.'])
        ->assertRedirect()
        ->assertSessionHas('status', 'Descripción actualizada.');

    expect($idea->fresh()->description)->toBe('Descripción actualizada por el dueño.');
});

test('un usuario que no es el dueño no puede editar la descripcion', function () {
    $otro = User::factory()->create();
    $otro->assignRole('beneficiario');

    $idea = ProjectIdea::factory()->create([
        'organizer_id' => $this->beneficiario->id,
        'description' => 'Descripción original.',
    ]);

    $this->actingAs($otro)
        ->patch("/ideas/{$idea->id}", ['description' => 'Intento de cambio.'])
        ->assertForbidden();

    expect($idea->fresh()->description)->toBe('Descripción original.');
});

test('la descripcion no puede quedar vacia', function () {
    $idea = ProjectIdea::factory()->create(['organizer_id' => $this->beneficiario->id]);

    $this->actingAs($this->beneficiario)
        ->patch("/ideas/{$idea->id}", ['description' => ''])
        ->assertSessionHasErrors('description');
});

test('un visitante no ve el boton editar idea', function () {
    $idea = ProjectIdea::factory()->create(['status' => 'completed']);

    $this->get("/ideas/{$idea->id}")
        ->assertOk()
        ->assertDontSee('Editar idea')
        ->assertSee('Adjuntos');
});
