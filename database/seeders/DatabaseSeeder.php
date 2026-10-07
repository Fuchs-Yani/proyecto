<?php

namespace Database\Seeders;

use App\Models\Attachment;
use App\Models\ProjectIdea;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Limpiar la caché interna de roles/permisos de Spatie
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Crear los roles principales
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $studentRole = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $orgRole = Role::firstOrCreate(['name' => 'organization', 'guard_name' => 'web']);

        $adminRole->syncPermissions(Permission::all());

        $studentRole->syncPermissions([
            'ViewAny:ProjectIdea',
            'View:ProjectIdea',
            'ViewAny:Assignment',
            'View:Assignment',
            'Create:Assignment',
        ]);

        $orgRole->syncPermissions([
            'ViewAny:ProjectIdea',
            'View:ProjectIdea',
            'Create:ProjectIdea',
            'Update:ProjectIdea',
            'ViewAny:Attachment',
            'View:Attachment',
            'Create:Attachment',
            'ViewAny:Assignment',
            'View:Assignment',
            'Update:Assignment',
        ]);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        // 3. Usuario Administrador (Acceso total a Filament)
        $admin = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Administrador General',
                'password' => Hash::make('password123'),
            ]
        );
        $admin->assignRole($adminRole);

        // 4. Usuario Estudiante
        $student = User::firstOrCreate(
            ['email' => 'estudiante@test.com'],
            [
                'name' => 'Estudiante de Prueba',
                'password' => Hash::make('password123'),
            ]
        );
        $student->assignRole($studentRole);

        // 5. Usuario Organización
        $org = User::firstOrCreate(
            ['email' => 'ong@test.com'],
            [
                'name' => 'Organización de Prueba',
                'password' => Hash::make('password123'),
            ]
        );
        $org->assignRole($orgRole);

        // 6. Datos demo (solo si no hay ideas)
        if (ProjectIdea::count() === 0) {
            $ideas = ProjectIdea::factory(5)->create(['organizer_id' => $org->id]);
            foreach ($ideas as $idea) {
                Attachment::factory(2)->create(['project_idea_id' => $idea->id]);
            }
            // Postulación demo del estudiante a la primera idea
            \App\Models\Assignment::firstOrCreate(
                ['project_idea_id' => $ideas->first()->id, 'student_id' => $student->id],
                ['status' => 'applied', 'applied_at' => now()]
            );
        }
    }
    
}
