<?php

use App\Models\ProjectIdea;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['ViewAny:ProjectIdea', 'View:ProjectIdea'] as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    Role::firstOrCreate(['name' => 'beneficiario', 'guard_name' => 'web'])
        ->syncPermissions(['ViewAny:ProjectIdea', 'View:ProjectIdea']);

    $this->beneficiario = User::factory()->create();
    $this->beneficiario->assignRole('beneficiario');
});

test('el buscador filtra las ideas por nombre', function () {
    ProjectIdea::factory()->create(['title' => 'Sistema de biblioteca escolar']);
    ProjectIdea::factory()->create(['title' => 'App de reciclaje urbano']);

    $this->actingAs($this->beneficiario)
        ->get('/ideas?search=biblioteca')
        ->assertOk()
        ->assertSee('Sistema de biblioteca escolar')
        ->assertDontSee('App de reciclaje urbano')
        ->assertSee('Resultados para');
});

test('el buscador encuentra ideas por el nombre del beneficiario', function () {
    $maria = User::factory()->create(['name' => 'María González']);

    ProjectIdea::factory()->create([
        'organizer_id' => $maria->id,
        'title' => 'Proyecto de huerta comunitaria',
    ]);
    ProjectIdea::factory()->create(['title' => 'Proyecto de robótica']);

    $this->actingAs($this->beneficiario)
        ->get('/ideas?search=María')
        ->assertOk()
        ->assertSee('Proyecto de huerta comunitaria')
        ->assertDontSee('Proyecto de robótica');
});

test('el buscador muestra un mensaje cuando no encuentra ideas', function () {
    ProjectIdea::factory()->create(['title' => 'App de reciclaje urbano']);

    $this->actingAs($this->beneficiario)
        ->get('/ideas?search=inexistente')
        ->assertOk()
        ->assertSee('No se encontraron ideas con ese nombre.');
});

test('el buscador tambien filtra en mis ideas', function () {
    ProjectIdea::factory()->create([
        'organizer_id' => $this->beneficiario->id,
        'title' => 'Mi idea de biblioteca',
    ]);
    ProjectIdea::factory()->create([
        'organizer_id' => $this->beneficiario->id,
        'title' => 'Mi idea de reciclaje',
    ]);

    $this->actingAs($this->beneficiario)
        ->get('/mis-ideas?search=reciclaje')
        ->assertOk()
        ->assertSee('Mi idea de reciclaje')
        ->assertDontSee('Mi idea de biblioteca');
});

test('el buscador conserva el filtro al paginar', function () {
    ProjectIdea::factory()->count(13)->create(['title' => 'Reciclaje']);

    $this->actingAs($this->beneficiario)
        ->get('/ideas?search=Reciclaje')
        ->assertOk()
        ->assertSee('search=Reciclaje', false);
});
