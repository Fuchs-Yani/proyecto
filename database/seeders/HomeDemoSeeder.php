<?php

namespace Database\Seeders;

use App\Models\ProjectIdea;
use App\Models\User;
use Illuminate\Database\Seeder;

class HomeDemoSeeder extends Seeder
{
    public function run(): void
    {
        $org = User::where('email', 'beneficiario@test.com')->first() ?? User::first();
        $data = [
            ['Calculadora', 'Pequeña calculadora web con operaciones básicas, historial y diseño sobrio para uso en clase.'],
            ['Sistema Administrativo para almacén', 'Gestión de stock, ventas y proveedores para un almacén barrial, con reportes simples.'],
            ['Sistema de reservas de hotel', 'Reservas de habitaciones con calendario, clientes y confirmación por email.'],
        ];
        foreach ($data as [$title, $desc]) {
            ProjectIdea::firstOrCreate(
                ['title' => $title, 'organizer_id' => $org->id],
                ['description' => $desc, 'status' => 'completed']
            );
        }
    }
}
