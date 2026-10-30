<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Envío de las solicitudes de contacto al CRM (EnviarContactoAlCrm).
 * - `crm_enviado_at`: cuándo la aceptó el CRM (null = no ha llegado).
 * - `crm_error`: última respuesta de rechazo o error, para poder revisarla.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('contactos', function (Blueprint $table) {
            $table->timestamp('crm_enviado_at')->nullable()->after('datos');
            $table->text('crm_error')->nullable()->after('crm_enviado_at');
        });
    }

    public function down(): void
    {
        Schema::table('contactos', function (Blueprint $table) {
            $table->dropColumn(['crm_enviado_at', 'crm_error']);
        });
    }
};
