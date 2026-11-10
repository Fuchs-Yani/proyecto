<?php

use App\Models\Attachment;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('el administrador puede abrir el formulario de crear adjunto', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get('/admin/attachments/create')
        ->assertOk();
});

test('el administrador puede abrir el formulario de editar adjunto', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $attachment = Attachment::factory()->create();

    $this->actingAs($admin)
        ->get("/admin/attachments/{$attachment->id}/edit")
        ->assertOk();
});
