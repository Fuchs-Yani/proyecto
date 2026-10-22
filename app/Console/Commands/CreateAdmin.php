<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin
                            {--name= : Nombre del administrador}
                            {--email= : Correo del administrador}
                            {--password= : Contraseña del administrador}';

    protected $description = 'Crea el usuario administrador inicial sin cargar datos demo';

    public function handle(): int
    {
        // Solo siembra roles/permisos si todavía no existen. Así no pisa
        // permisos personalizados si el comando se vuelve a ejecutar.
        if (Role::where('name', 'admin')->doesntExist()) {
            $this->callSilently('db:seed', [
                '--class' => DatabaseSeeder::class,
                '--force' => true,
            ]);
        }

        $name = $this->option('name') ?: $this->ask('Nombre');
        $email = strtolower(trim((string) ($this->option('email') ?: $this->ask('Correo electrónico'))));
        $password = $this->option('password') ?: $this->secret('Contraseña (mínimo 8 caracteres)');

        $validator = Validator::make(
            compact('name', 'email', 'password'),
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $user->assignRole('admin');

        $this->info("Administrador creado: {$email}");

        return self::SUCCESS;
    }
}
