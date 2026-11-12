<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Origen de la solicitud y consentimiento de comunicaciones comerciales.
 * - `origen`: de dónde llegó la persona (utm_source, utm_medium, utm_campaign, utm_content, utm_term,
 *   página de entrada y web de procedencia). Solo lo que traiga el enlace; null si entró directa.
 * - `marketing_consent_at`: cuándo marcó la casilla de recibir novedades de DuaLab (null = no la marcó).
 *   Es una casilla aparte y opcional: enviar la solicitud no depende de ella.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('contactos', function (Blueprint $table) {
            $table->json('origen')->nullable()->after('datos');
            $table->timestamp('marketing_consent_at')->nullable()->after('origen');
        });
    }

    public function down(): void
    {
        Schema::table('contactos', function (Blueprint $table) {
            $table->dropColumn(['origen', 'marketing_consent_at']);
        });
    }
};
