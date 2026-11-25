<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Gestión web — el rol del equipo que atiende los formularios pasa de `contactos` a
 * `atencion_cliente`, para no confundirlo con la tabla `contactos` (quien nos escribe).
 */
return new class extends Migration {
    public function up(): void
    {
        DB::table('users')->where('role', 'contactos')->update(['role' => 'atencion_cliente']);
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'atencion_cliente')->update(['role' => 'contactos']);
    }
};
