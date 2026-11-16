<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gestión web — registro de actividad: quién hizo qué en el panel y cuándo.
 * Sin datos personales de terceros (los formularios se citan por su número).
 * `usuario_nombre` conserva el nombre aunque la cuenta se borre.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('actividad_panel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('usuario_nombre', 120);
            $table->string('seccion', 30);
            $table->string('accion', 30);
            $table->string('descripcion', 255);
            // Elemento afectado: tipo (pagina, entrada, medio…) + id o clave
            $table->string('objeto_tipo', 30)->nullable();
            $table->string('objeto_ref', 60)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['user_id', 'created_at']);
            $table->index(['seccion', 'created_at']);
            $table->index(['objeto_tipo', 'objeto_ref']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividad_panel');
    }
};
