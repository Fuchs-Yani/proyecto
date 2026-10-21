<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Guardaremos el rol directamente como texto ('beneficiario', 'profesor', 'student')
            $table->string('role')->default('student')->after('email');
            // Guardaremos la institución opcionalmente
            $table->string('institution_name')->nullable()->after('role');
        });

        // Backfill: copia el rol de Spatie a la nueva columna
        DB::table('users')->where('is_admin', true)->update(['role' => 'admin']);

        foreach (['student', 'beneficiario', 'profesor'] as $roleName) {
            $userIds = DB::table('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('roles.name', $roleName)
                ->where('model_has_roles.model_type', 'App\\Models\\User')
                ->pluck('model_has_roles.model_id');

            DB::table('users')->whereIn('id', $userIds)->update(['role' => $roleName]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'institution_name']);
        });
    }
};
