<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gestión web — idiomas de la web pública (equivalente al plugin multidioma).
 * El español queda creado y por defecto: sin él la web no tendría idioma base.
 * El resto se añade desde el panel (lista blanca en config/cms.php).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('idiomas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 5)->unique();
            $table->string('nombre', 60);
            $table->boolean('activo')->default(false);
            $table->boolean('por_defecto')->default(false);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });

        DB::table('idiomas')->insert([
            'codigo'      => 'es',
            'nombre'      => 'Español',
            'activo'      => true,
            'por_defecto' => true,
            'orden'       => 0,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('idiomas');
    }
};
