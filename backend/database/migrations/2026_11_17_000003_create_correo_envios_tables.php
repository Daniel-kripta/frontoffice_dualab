<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gestión web — correo: bandeja de salida. Cada envío desde el panel queda aquí mientras
 * sale por la cola (EnviarCorreoJob); lo enviado vuelve después como copia de «Enviados».
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('correo_envios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('buzon', 40);
            $table->string('tipo', 20)->default('nuevo');           // nuevo | responder | reenviar
            // Correo al que responde o que reenvía (hilo: In-Reply-To / References)
            $table->foreignId('correo_mensaje_id')->nullable()->constrained('correo_mensajes')->nullOnDelete();
            $table->json('para');
            $table->json('cc')->nullable();
            $table->json('cco')->nullable();
            $table->string('asunto', 255);
            $table->mediumText('texto');
            $table->string('message_id', 255)->unique();
            $table->string('in_reply_to', 255)->nullable();
            $table->text('referencias')->nullable();
            $table->string('estado', 20)->default('pendiente');     // pendiente | enviado | error
            $table->string('error', 255)->nullable();
            $table->boolean('copia_en_enviados')->default(false);
            $table->timestamp('enviado_en')->nullable();
            $table->timestamps();

            $table->index(['buzon', 'estado', 'created_at']);
        });

        Schema::create('correo_envio_adjuntos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('correo_envio_id')->constrained('correo_envios')->cascadeOnDelete();
            $table->string('nombre', 255);
            $table->string('tipo', 150)->nullable();
            $table->unsignedInteger('tamano')->default(0);
            $table->string('ruta', 255);                             // disco privado
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correo_envio_adjuntos');
        Schema::dropIfExists('correo_envios');
    }
};
