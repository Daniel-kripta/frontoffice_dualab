<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Historial: qué cambió en cada acción (lista de { campo, antes, despues }), para abrir el detalle.
// Las filas anteriores se quedan sin detalle.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actividad_panel', function (Blueprint $table) {
            $table->json('detalle')->nullable()->after('objeto_ref');
        });
    }

    public function down(): void
    {
        Schema::table('actividad_panel', function (Blueprint $table) {
            $table->dropColumn('detalle');
        });
    }
};
