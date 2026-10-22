<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Crea únicamente roles y permisos.
     *
     * No crea usuarios, ideas, postulaciones ni adjuntos de ejemplo.
     * Los datos reales se cargan desde la aplicación o con el comando
     * `php artisan app:create-admin` para el primer administrador.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $studentRole = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $beneficiarioRole = Role::firstOrCreate(['name' => 'beneficiario', 'guard_name' => 'web']);
        $profesorRole = Role::firstOrCreate(['name' => 'profesor', 'guard_name' => 'web']);

        // Permisos base usados por las policies de la aplicación.
        $needed = [
            'ViewAny:ProjectIdea', 'View:ProjectIdea', 'Create:ProjectIdea', 'Update:ProjectIdea', 'Delete:ProjectIdea',
            'ViewAny:Assignment', 'View:Assignment', 'Create:Assignment', 'Update:Assignment', 'Delete:Assignment',
            'ViewAny:Attachment', 'View:Attachment', 'Create:Attachment', 'Update:Attachment', 'Delete:Attachment',
        ];

        foreach ($needed as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // El admin recibe todos los permisos existentes, incluidos los de Shield.
        $adminRole->syncPermissions(Permission::all());

        // STUDENT: ve ideas, se postula/retira y descarga adjuntos.
        $studentRole->syncPermissions([
            'ViewAny:ProjectIdea',
            'View:ProjectIdea',
            'ViewAny:Assignment',
            'View:Assignment',
            'Create:Assignment',
            'Delete:Assignment',
            'ViewAny:Attachment',
            'View:Attachment',
        ]);

        // BENEFICIARIO: gestiona sus ideas/adjuntos y las postulaciones recibidas.
        $beneficiarioRole->syncPermissions([
            'ViewAny:ProjectIdea',
            'View:ProjectIdea',
            'Create:ProjectIdea',
            'Update:ProjectIdea',
            'Delete:ProjectIdea',
            'ViewAny:Attachment',
            'View:Attachment',
            'Create:Attachment',
            'Delete:Attachment',
            'ViewAny:Assignment',
            'View:Assignment',
            'Update:Assignment',
        ]);

        // PROFESOR: ve ideas/adjuntos y puede actualizar postulaciones.
        $profesorRole->syncPermissions([
            'ViewAny:ProjectIdea',
            'View:ProjectIdea',
            'ViewAny:Assignment',
            'View:Assignment',
            'Update:Assignment',
            'ViewAny:Attachment',
            'View:Attachment',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info('Roles y permisos listos. No se cargaron datos demo.');
    }
}
