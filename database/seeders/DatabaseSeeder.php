<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

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
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $studentRole = Role::firstOrCreate(['name' => 'student']);
        $orgRole = Role::firstOrCreate(['name' => 'organization']);

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
    }
}
