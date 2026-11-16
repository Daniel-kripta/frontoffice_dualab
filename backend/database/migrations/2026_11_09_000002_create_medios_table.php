<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Gestión web — biblioteca de medios (imágenes, vídeos y documentos de la web). */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('medios', function (Blueprint $table) {
            $table->id();
            $table->string('ruta')->unique();
            $table->string('nombre_original');
            $table->string('mime', 100);
            $table->string('tipo', 12)->index(); // imagen | video | documento
            $table->unsignedBigInteger('tamano');
            $table->unsignedInteger('ancho')->nullable();
            $table->unsignedInteger('alto')->nullable();
            $table->string('titulo')->nullable();
            $table->string('alt')->nullable();
            $table->foreignId('subido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medios');
    }
};
