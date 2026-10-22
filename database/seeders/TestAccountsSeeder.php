<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestAccountsSeeder extends Seeder
{
    /**
     * Crea únicamente las cuentas de prueba.
     *
     * No se ejecuta desde DatabaseSeeder. Para usarlo:
     * php artisan db:seed --class=TestAccountsSeeder
     */
    public function run(): void
    {
        // Garantiza que existan los roles y permisos antes de asignarlos.
        $this->call(DatabaseSeeder::class);

        $accounts = [
            [
                'name' => 'Administrador General',
                'email' => 'admin@admin.com',
                'role' => 'admin',
            ],
            [
                'name' => 'Estudiante de Prueba',
                'email' => 'estudiante@test.com',
                'role' => 'student',
            ],
            [
                'name' => 'Beneficiario de Prueba',
                'email' => 'beneficiario@test.com',
                'role' => 'beneficiario',
                'institution_name' => 'Institución de Prueba',
            ],
            [
                'name' => 'Profesor de Prueba',
                'email' => 'profesor@test.com',
                'role' => 'profesor',
            ],
        ];

        foreach ($accounts as $account) {
            $user = User::firstOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => Hash::make('password123'),
                    'institution_name' => $account['institution_name'] ?? null,
                ],
            );

            $user->syncRoles([$account['role']]);
        }

        $this->command?->info('Cuentas de prueba listas. Contraseña: password123');
    }
}
