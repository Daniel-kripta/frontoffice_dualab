<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gestión web — SEO por página/entrada e idioma (equivalente a AIOSEO) y ajustes
 * generales del sitio (SEO global, analítica tipo Site Kit, integraciones de
 * formularios). Los ajustes con secretos se guardan cifrados (ver Ajuste).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('seo_metadatos', function (Blueprint $table) {
            $table->id();
            $table->morphs('seoable');
            $table->string('idioma', 5);
            $table->string('meta_titulo', 120)->nullable();
            $table->string('meta_descripcion', 320)->nullable();
            $table->string('palabra_clave', 80)->nullable();
            $table->string('imagen_og')->nullable();
            $table->string('canonical')->nullable();
            $table->boolean('noindex')->default(false);
            $table->timestamps();

            $table->unique(['seoable_type', 'seoable_id', 'idioma']);
        });

        Schema::create('ajustes', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 80)->unique();
            $table->text('valor')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ajustes');
        Schema::dropIfExists('seo_metadatos');
    }
};
