<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Redirecciones 301 de la web pública: hoy, las de una noticia publicada cuya dirección cambia
// (la antigua lleva a la nueva y no se pierde lo que Google y las IA ya tenían).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('redirecciones', function (Blueprint $table) {
            $table->id();
            $table->string('idioma', 5);
            $table->string('origen', 255);   // /noticias/direccion-antigua
            $table->string('destino', 255);  // /noticias/direccion-nueva
            $table->timestamps();
            $table->unique(['idioma', 'origen']);
            $table->index(['idioma', 'destino']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redirecciones');
    }
};
