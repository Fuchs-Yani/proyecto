<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Renombra rol persistido organization -> beneficiario
        DB::table('roles')->where('name', 'organization')->update(['name' => 'beneficiario']);

        // Actualiza usuario demo si existe
        DB::table('users')->where('email', 'ong@test.com')->update([
            'email' => 'beneficiario@test.com',
            'name' => 'Beneficiario de Prueba',
        ]);
    }

    public function down(): void
    {
        DB::table('roles')->where('name', 'beneficiario')->update(['name' => 'organization']);

        DB::table('users')->where('email', 'beneficiario@test.com')->update([
            'email' => 'ong@test.com',
            'name' => 'Organización de Prueba',
        ]);
    }
};
