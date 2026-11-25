<?php

namespace App\Support;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Jobs que no deben esperar al cron de la cola: en Hostinger compartido la cola se vacía una vez por
 * minuto (routes/console.php), y un correo que llega de Central y espera aquí otra vuelta de cola
 * podía tardar varios minutos. Se ejecutan nada más enviar la respuesta HTTP (quien lo pide no espera)
 * y, si fallan, van a la cola como siempre, con sus reintentos.
 *
 * Solo tras el commit de la transacción en curso: si se deshace, no sale nada. Fuera de una petición
 * HTTP (comandos, scheduler, tests), directamente a la cola.
 */
class EnvioAlResponder
{
    public static function despachar(ShouldQueue $job): void
    {
        if (app()->runningInConsole()) {
            dispatch($job);

            return;
        }

        DB::afterCommit(fn () => app()->terminating(fn () => self::ejecutar($job)));
    }

    /** Lo ejecuta ya; si falla, a la cola (el job debe poder repetirse sin duplicar). */
    public static function ejecutar(ShouldQueue $job): void
    {
        try {
            app()->call([$job, 'handle']);
        } catch (Throwable $e) {
            Log::info('Envío inmediato fallido: pasa a la cola.', ['job' => class_basename($job), 'error' => class_basename($e)]);
            dispatch($job);
        }
    }
}
