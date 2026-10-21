<?php

use App\Models\ProjectIdea;

test('invitados ven solo proyectos terminados en el catalogo', function () {
    ProjectIdea::factory()->create(['title' => 'Proyecto Terminado', 'status' => 'completed']);
    ProjectIdea::factory()->create(['title' => 'Idea Disponible', 'status' => 'available']);

    $this->get('/ideas')
        ->assertOk()
        ->assertSee('Proyecto Terminado')
        ->assertDontSee('Idea Disponible');
});

test('invitados pueden ver el detalle de un proyecto terminado', function () {
    $project = ProjectIdea::factory()->create(['status' => 'completed']);
    \App\Models\Attachment::factory()->create(['project_idea_id' => $project->id]);

    $this->get("/ideas/{$project->id}")->assertOk();
});

test('invitados no pueden ver el detalle de una idea disponible', function () {
    $project = ProjectIdea::factory()->create(['status' => 'available']);

    $this->get("/ideas/{$project->id}")->assertNotFound();
});
