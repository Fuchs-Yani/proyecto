<?php

use App\Models\ProjectIdea;
use App\Models\Tag;
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

test('al publicar una idea se guardan sus etiquetas', function () {
    $this->actingAs($this->beneficiario)->post('/ideas', [
        'title' => 'Sistema para la biblioteca',
        'description' => 'Catalogar libros y préstamos.',
        'tags' => 'educación, gestión ,Educación',
    ])->assertRedirect();

    $idea = ProjectIdea::where('title', 'Sistema para la biblioteca')->firstOrFail();

    expect($idea->tags->pluck('name')->all())->toEqualCanonicalizing(['educación', 'gestión']);

    $this->assertDatabaseHas('tags', ['name' => 'educación']);
});

test('como maximo se guardan tres etiquetas por idea', function () {
    $this->actingAs($this->beneficiario)->post('/ideas', [
        'title' => 'Idea con muchas etiquetas',
        'description' => 'Prueba del límite.',
        'tags' => 'uno, dos, tres, cuatro, cinco',
    ])->assertRedirect();

    $idea = ProjectIdea::where('title', 'Idea con muchas etiquetas')->firstOrFail();

    expect($idea->tags)->toHaveCount(ProjectIdea::MAX_TAGS)
        ->and($idea->tags->pluck('name')->all())->toEqualCanonicalizing(['uno', 'dos', 'tres']);
});

test('buscar una etiqueta exacta muestra solo las ideas con esa etiqueta', function () {
    $conTag = ProjectIdea::factory()->create(['title' => 'Recordatorio de medicación']);
    $conTag->tags()->attach(Tag::create(['name' => 'salud']));

    ProjectIdea::factory()->create(['title' => 'App de salud para mascotas']);

    $this->actingAs($this->beneficiario)
        ->get('/ideas?search=salud')
        ->assertOk()
        ->assertSee('Recordatorio de medicación')
        ->assertDontSee('App de salud para mascotas')
        ->assertSee('Ideas con la etiqueta «salud»');
});

test('la busqueda por etiqueta no distingue mayusculas', function () {
    $conTag = ProjectIdea::factory()->create(['title' => 'Catálogo de libros']);
    $conTag->tags()->attach(Tag::create(['name' => 'Educación']));

    $this->actingAs($this->beneficiario)
        ->get('/ideas?search=EDUCACIÓN')
        ->assertOk()
        ->assertSee('Catálogo de libros')
        ->assertSee('Ideas con la etiqueta «Educación»');
});

test('el buscador encuentra ideas por el nombre de una etiqueta', function () {
    $idea = ProjectIdea::factory()->create(['title' => 'Sistema de biblioteca escolar']);
    $idea->tags()->attach(Tag::create(['name' => 'educación']));

    ProjectIdea::factory()->create(['title' => 'App de reciclaje urbano']);

    $this->actingAs($this->beneficiario)
        ->get('/ideas?search=educación')
        ->assertOk()
        ->assertSee('Sistema de biblioteca escolar')
        ->assertDontSee('App de reciclaje urbano');
});

test('el filtro por etiqueta muestra solo las ideas con esa etiqueta', function () {
    $tag = Tag::create(['name' => 'salud']);

    $conTag = ProjectIdea::factory()->create(['title' => 'Recordatorio de medicamentos']);
    $conTag->tags()->attach($tag);

    ProjectIdea::factory()->create(['title' => 'Sistema de inventario']);

    $this->actingAs($this->beneficiario)
        ->get('/ideas?tag='.$tag->id)
        ->assertOk()
        ->assertSee('Recordatorio de medicamentos')
        ->assertDontSee('Sistema de inventario')
        ->assertSee('salud');
});

test('el dueño de la idea puede actualizar sus etiquetas', function () {
    $idea = ProjectIdea::factory()->create(['organizer_id' => $this->beneficiario->id]);
    $idea->tags()->attach(Tag::create(['name' => 'vieja']));

    $this->actingAs($this->beneficiario)
        ->patch("/ideas/{$idea->id}/etiquetas", ['tags' => 'nueva, salud'])
        ->assertRedirect();

    expect($idea->fresh()->tags->pluck('name')->all())->toEqualCanonicalizing(['nueva', 'salud']);
});

test('un usuario que no es el dueño no puede actualizar las etiquetas', function () {
    $otro = User::factory()->create();
    $otro->assignRole('beneficiario');

    $idea = ProjectIdea::factory()->create(['organizer_id' => $this->beneficiario->id]);

    $this->actingAs($otro)
        ->patch("/ideas/{$idea->id}/etiquetas", ['tags' => 'intruso'])
        ->assertForbidden();

    expect($idea->fresh()->tags)->toBeEmpty();
});

test('las etiquetas se muestran en el listado y en el detalle de la idea', function () {
    $idea = ProjectIdea::factory()->create([
        'organizer_id' => $this->beneficiario->id,
        'title' => 'Sistema de biblioteca escolar',
    ]);
    $idea->tags()->attach(Tag::create(['name' => 'educación']));

    $this->actingAs($this->beneficiario)
        ->get('/ideas')
        ->assertOk()
        ->assertSee('#educación');

    $this->actingAs($this->beneficiario)
        ->get("/ideas/{$idea->id}")
        ->assertOk()
        ->assertSee('#educación')
        ->assertSee('Guardar etiquetas');
});
