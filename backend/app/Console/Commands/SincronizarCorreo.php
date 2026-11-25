<?php

namespace App\Console\Commands;

use App\Services\CorreoSincronizacionService;
use Illuminate\Console\Command;

/** Copia a la BD lo nuevo de los buzones de config/correo.php (lo lanza el programador). */
class SincronizarCorreo extends Command
{
    protected $signature = 'correo:sincronizar {buzon? : clave del buzón en config/correo.php (por defecto, todos)}';

    protected $description = 'Copia los correos nuevos de Hostinger (IMAP) a la bandeja de Gestión web';

    public function handle(CorreoSincronizacionService $servicio): int
    {
        $buzones = $this->argument('buzon') ? [$this->argument('buzon')] : array_keys(config('correo.buzones'));

        foreach ($buzones as $buzon) {
            if (! config("correo.buzones.{$buzon}.password")) {
                $this->line("{$buzon}: sin contraseña en el .env, se omite.");
                continue;
            }
            $resultado = $servicio->sincronizar($buzon);
            if (isset($resultado['error'])) {
                $this->error("{$buzon}: no se ha podido sincronizar ({$resultado['error']}).");
                continue;
            }
            foreach ($resultado as $carpeta => $copiados) {
                $this->info("{$buzon}/{$carpeta}: {$copiados} mensajes nuevos.");
            }
        }

        return self::SUCCESS;
    }
}
