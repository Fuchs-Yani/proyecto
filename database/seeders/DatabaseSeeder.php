<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Carga el escenario completo de demostración con un solo comando:
     * roles y permisos, cuentas de los cuatro roles, proyectos en todos los
     * estados, postulaciones, avances, adjuntos y notificaciones.
     *
     * Uso:
     * php artisan db:seed
     * php artisan migrate --seed        (en una base nueva)
     * php artisan migrate:fresh --seed  (borra y recrea todo)
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(DemoDataSeeder::class);

        $this->command?->info('Base de datos lista con el escenario demo completo.');
    }
}
