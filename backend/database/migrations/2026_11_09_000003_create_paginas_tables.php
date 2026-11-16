<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gestión web — páginas de la web pública y sus textos editables.
 * Qué páginas y campos existen lo define config/cms_paginas.php (lista blanca);
 * aquí solo se guarda lo que se ha cambiado respecto al texto original.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('paginas', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 60)->unique();
            $table->boolean('publicada')->default(true);
            $table->json('idiomas_activos')->nullable();
            $table->foreignId('editada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('contenidos_pagina', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pagina_id')->constrained('paginas')->cascadeOnDelete();
            $table->string('idioma', 5);
            $table->string('campo', 100);
            $table->text('valor')->nullable();
            $table->timestamps();

            $table->unique(['pagina_id', 'idioma', 'campo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contenidos_pagina');
        Schema::dropIfExists('paginas');
    }
};
