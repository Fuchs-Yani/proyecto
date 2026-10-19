<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function run(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Guardaremos el rol directamente como texto ('beneficiario', 'profesor', 'estudiante')
            $table->string('role')->default('estudent')->after('email');
            // Guardaremos la institución opcionalmente
            $table->string('institution_name')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'institution_name']);
        });
    }
};