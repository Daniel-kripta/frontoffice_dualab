<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gestión web — entradas del blog/noticias y sus categorías.
 * Cada traducción es una entrada propia (con su slug y su SEO) unida a las demás
 * por `grupo_traduccion`, como hacen los plugins multidioma.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80);
            $table->string('slug', 100)->unique();
            $table->string('color', 20)->default('centros');
            $table->timestamps();
        });

        Schema::create('entradas', function (Blueprint $table) {
            $table->id();
            $table->string('idioma', 5)->default('es');
            $table->uuid('grupo_traduccion')->index();
            $table->string('titulo', 200);
            $table->string('slug', 220);
            $table->string('extracto', 500)->nullable();
            $table->longText('contenido')->nullable();
            $table->string('imagen_destacada')->nullable();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->nullOnDelete();
            $table->string('estado', 12)->default('borrador'); // borrador | publicada
            $table->timestamp('publicada_en')->nullable();
            $table->boolean('destacada')->default(false);
            $table->foreignId('autor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['idioma', 'slug']);
            $table->index(['estado', 'idioma', 'publicada_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entradas');
        Schema::dropIfExists('categorias');
    }
};
