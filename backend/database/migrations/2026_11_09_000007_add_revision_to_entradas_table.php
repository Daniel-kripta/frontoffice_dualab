<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gestión web — aprobación de entradas antes de verse en la web.
 * Estados (columna `estado`, ya existente): borrador → pendiente → publicada.
 * - El editor guarda borradores y los envía a revisión (pendiente).
 * - Un administrador aprueba (publicada) o la devuelve con una nota (borrador).
 * - Un administrador puede retirar de la web una publicada (vuelve a borrador).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('entradas', function (Blueprint $table) {
            $table->timestamp('enviada_revision_en')->nullable()->after('publicada_en');
            $table->foreignId('aprobada_por')->nullable()->after('enviada_revision_en')->constrained('users')->nullOnDelete();
            $table->timestamp('aprobada_en')->nullable()->after('aprobada_por');
            $table->string('nota_revision', 500)->nullable()->after('aprobada_en');
        });
    }

    public function down(): void
    {
        Schema::table('entradas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('aprobada_por');
            $table->dropColumn(['enviada_revision_en', 'aprobada_en', 'nota_revision']);
        });
    }
};
