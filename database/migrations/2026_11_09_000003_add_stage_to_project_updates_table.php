<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_updates', function (Blueprint $table) {
            // Etapa del proyecto a la que corresponde el avance.
            // Los registros existentes quedan en "desarrollo" para no perder su historial.
            $table->string('stage')->default('desarrollo')->after('repository_url');
        });
    }

    public function down(): void
    {
        Schema::table('project_updates', function (Blueprint $table) {
            $table->dropColumn('stage');
        });
    }
};
