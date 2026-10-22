<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProfessorAccountSeeder extends Seeder
{
    /**
     * Crea un profesor adicional de prueba.
     *
     * No se ejecuta desde DatabaseSeeder. Para usarlo:
     * php artisan db:seed --class=ProfessorAccountSeeder
     */
    public function run(): void
    {
        $this->call(DatabaseSeeder::class);

        $professor = User::updateOrCreate(
            ['email' => 'ricardo.salas@test.com'],
            [
                'name' => 'Ricardo Salas',
                'password' => Hash::make('password123'),
            ],
        );

        $professor->syncRoles(['profesor']);

        $this->command?->info('Profesor adicional creado: ricardo.salas@test.com · Contraseña: password123');
    }
}
