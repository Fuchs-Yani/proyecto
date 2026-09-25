<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
        $table->foreignId('project_idea_id')->constrained()->cascadeOnDelete();
            $table->string('file_path'); // Ruta del archivo en storage
            $table->string('file_name'); // Nombre original del archivo
            $table->string('mime_type')->nullable(); // image/png, application/pdf, etc.
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
