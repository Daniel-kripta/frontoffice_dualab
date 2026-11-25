<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Correo — envíos con diseño: DuaLab Central puede mandar, además del texto, una versión HTML ya
 * maquetada (p. ej. la invitación de una solicitud de retos). Se guarda limpia con HTMLPurifier
 * (perfil 'correo') y EnviarCorreoJob la usa en lugar de la que monta a partir del texto.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('correo_envios', function (Blueprint $table) {
            $table->longText('html')->nullable()->after('texto');
        });
    }

    public function down(): void
    {
        Schema::table('correo_envios', function (Blueprint $table) {
            $table->dropColumn('html');
        });
    }
};
