<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gestión web — bandeja de formularios recibidos (equivalente a Flamingo).
 * - `estado`: nuevo | leido | archivado.
 * - `pagina_origen`: ruta de la web desde la que se envió (para clasificarlos).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('contactos', function (Blueprint $table) {
            $table->string('estado', 12)->default('nuevo')->after('datos');
            $table->timestamp('leido_en')->nullable()->after('estado');
            $table->string('pagina_origen', 255)->nullable()->after('leido_en');
            $table->index(['estado', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('contactos', function (Blueprint $table) {
            $table->dropIndex(['estado', 'created_at']);
            $table->dropColumn(['estado', 'leido_en', 'pagina_origen']);
        });
    }
};
