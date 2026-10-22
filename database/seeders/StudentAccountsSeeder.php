<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StudentAccountsSeeder extends Seeder
{
    /**
     * Crea 7 estudiantes de prueba.
     *
     * No se ejecuta desde DatabaseSeeder. Para usarlo:
     * php artisan db:seed --class=StudentAccountsSeeder
     */
    public function run(): void
    {
        // Garantiza que existan los roles y permisos antes de asignarlos.
        $this->call(DatabaseSeeder::class);

        $students = [
            ['name' => 'Juan Pérez', 'email' => 'juan.perez@test.com'],
            ['name' => 'Sofía Ramírez', 'email' => 'sofia.ramirez@test.com'],
            ['name' => 'Mateo Álvarez', 'email' => 'mateo.alvarez@test.com'],
            ['name' => 'Valentina Torres', 'email' => 'valentina.torres@test.com'],
            ['name' => 'Nicolás Herrera', 'email' => 'nicolas.herrera@test.com'],
            ['name' => 'Camila Rojas', 'email' => 'camila.rojas@test.com'],
            ['name' => 'Lucas Medina', 'email' => 'lucas.medina@test.com'],
        ];

        foreach ($students as $student) {
            $user = User::updateOrCreate(
                ['email' => $student['email']],
                [
                    'name' => $student['name'],
                    'password' => Hash::make('password123'),
                ],
            );

            $user->syncRoles(['student']);
        }

        $this->command?->info('7 estudiantes de prueba creados. Contraseña: password123');
    }
}
