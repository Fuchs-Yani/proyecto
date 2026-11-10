<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Crea únicamente roles y permisos.
     *
     * Es la base que usan todos los demás seeders. No crea usuarios, ideas,
     * postulaciones ni adjuntos.
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
            'ViewAny:ProjectUpdate', 'View:ProjectUpdate', 'Create:ProjectUpdate', 'Update:ProjectUpdate', 'Delete:ProjectUpdate',
            'ViewAny:Attachment', 'View:Attachment', 'Create:Attachment', 'Update:Attachment', 'Delete:Attachment',
            // Permisos de gestión de roles (Filament Shield).
            'ViewAny:Role', 'View:Role', 'Create:Role', 'Update:Role', 'Delete:Role',
            'DeleteAny:Role', 'Restore:Role', 'ForceDelete:Role', 'ForceDeleteAny:Role',
            'RestoreAny:Role', 'Replicate:Role', 'Reorder:Role',
        ];

        foreach ($needed as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Solo debe existir el rol admin; elimina el super_admin legado de Shield.
        Role::where('name', 'super_admin')->delete();

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
            'View:ProjectUpdate',
            'Create:ProjectUpdate',
            'ViewAny:Attachment',
            'View:Attachment',
        ]);

        // BENEFICIARIO: gestiona sus ideas/adjuntos y solo consulta las postulaciones recibidas.
        // Aceptar o rechazar postulantes es tarea exclusiva del profesor/admin (AssignmentPolicy::review).
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
            'View:ProjectUpdate',
        ]);

        // PROFESOR: ve ideas/adjuntos y puede actualizar postulaciones.
        $profesorRole->syncPermissions([
            'ViewAny:ProjectIdea',
            'View:ProjectIdea',
            'ViewAny:Assignment',
            'View:Assignment',
            'Update:Assignment',
            'ViewAny:ProjectUpdate',
            'View:ProjectUpdate',
            'Update:ProjectUpdate',
            'ViewAny:Attachment',
            'View:Attachment',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
