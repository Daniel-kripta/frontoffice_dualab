<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gestión web — correo: un envío puede responder a un formulario recibido (tabla `contactos`),
 * para ver en la bandeja de Formularios qué se le contestó, cuándo y quién.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('correo_envios', function (Blueprint $table) {
            $table->foreignId('contacto_id')->nullable()->after('correo_mensaje_id')->constrained('contactos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('correo_envios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contacto_id');
        });
    }
};
