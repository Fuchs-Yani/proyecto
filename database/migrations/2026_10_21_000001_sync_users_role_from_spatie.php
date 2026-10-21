<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Sincroniza users.role con el rol real de Spatie
        foreach (['admin', 'student', 'beneficiario', 'profesor'] as $roleName) {
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
        // Sin reversa: la columna role sigue existiendo
    }
};
