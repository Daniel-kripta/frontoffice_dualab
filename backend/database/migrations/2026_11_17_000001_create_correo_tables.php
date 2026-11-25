<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gestión web — correo: copia local de los buzones de Hostinger (ver config/correo.php).
 * Un mensaje se identifica por buzón + carpeta + uidvalidity + uid (lo que da el IMAP).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('correo_mensajes', function (Blueprint $table) {
            $table->id();
            $table->string('buzon', 40);
            $table->string('carpeta', 100);
            $table->unsignedBigInteger('uidvalidity');
            $table->unsignedBigInteger('uid');
            // Para agrupar conversaciones y responder (F3)
            $table->string('message_id', 255)->nullable()->index();
            $table->string('in_reply_to', 255)->nullable();
            $table->text('referencias')->nullable();
            $table->string('de_email', 255);
            $table->string('de_nombre', 255)->nullable();
            $table->json('para')->nullable();
            $table->json('cc')->nullable();
            $table->json('responder_a')->nullable();
            $table->string('asunto', 500)->nullable();
            $table->timestamp('fecha')->index();
            $table->string('extracto', 300)->nullable();
            $table->mediumText('texto')->nullable();
            $table->mediumText('html')->nullable();      // ya limpio (HTMLPurifier, perfil `correo`)
            $table->boolean('tiene_adjuntos')->default(false);
            $table->string('estado', 20)->default('nuevo');
            $table->timestamp('leido_en')->nullable();
            $table->timestamps();

            $table->unique(['buzon', 'carpeta', 'uidvalidity', 'uid'], 'correo_mensajes_imap_unique');
            $table->index(['buzon', 'estado', 'fecha']);
        });

        Schema::create('correo_adjuntos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('correo_mensaje_id')->constrained('correo_mensajes')->cascadeOnDelete();
            $table->string('nombre', 255);
            $table->string('tipo', 150)->nullable();
            $table->unsignedInteger('tamano')->default(0);
            $table->boolean('en_linea')->default(false);
            // Disco privado; null si supera config('correo.max_adjunto_mb') (se abre en el webmail)
            $table->string('ruta', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('correo_sincronizaciones', function (Blueprint $table) {
            $table->id();
            $table->string('buzon', 40);
            $table->string('carpeta', 100);
            $table->unsignedBigInteger('uidvalidity')->nullable();
            $table->unsignedBigInteger('ultimo_uid')->default(0);
            // Un mensaje que falla se reintenta en las siguientes pasadas; a los 3 fallos se salta
            // (queda solo en el webmail) y se cuenta en `omitidos` para avisar en el panel
            $table->unsignedBigInteger('uid_fallido')->nullable();
            $table->unsignedTinyInteger('intentos_fallidos')->default(0);
            $table->unsignedInteger('omitidos')->default(0);
            $table->timestamp('sincronizado_en')->nullable();
            $table->string('ultimo_error', 255)->nullable();
            $table->timestamp('error_en')->nullable();
            $table->timestamps();

            $table->unique(['buzon', 'carpeta']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correo_adjuntos');
        Schema::dropIfExists('correo_mensajes');
        Schema::dropIfExists('correo_sincronizaciones');
    }
};
