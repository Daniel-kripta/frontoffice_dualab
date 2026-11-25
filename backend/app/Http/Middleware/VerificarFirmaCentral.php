<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * API de servidor a servidor para DuaLab Central (espacio «Primer contacto»): el backend de la app
 * DuaLab pide aquí los formularios de la web. Sin sesión ni cookies: lo protege una firma HMAC-SHA256
 * con un secreto compartido (services.central.secreto = CENTRAL_API_SECRETO en los dos .env).
 *
 * Se firma «fecha \n MÉTODO \n ruta \n query ordenada \n usuario \n cuerpo». La fecha (Unix) caduca a
 * los 5 minutos para que una petición capturada no sirva más tarde. El usuario (nombre del
 * superadmin de Central) va firmado porque se guarda en el registro de actividad.
 */
class VerificarFirmaCentral
{
    private const LONGITUD_MINIMA_SECRETO = 32;
    private const VENTANA_SEGUNDOS = 300;

    public function handle(Request $request, Closure $next): Response
    {
        $secreto = (string) config('services.central.secreto');
        if (strlen($secreto) < self::LONGITUD_MINIMA_SECRETO) {
            return response()->json(['error' => 'Integración no configurada.'], 503);
        }

        $fecha = (string) $request->header('X-DuaLab-Fecha', '');
        $firma = (string) $request->header('X-DuaLab-Firma', '');
        if (!ctype_digit($fecha) || abs(time() - (int) $fecha) > self::VENTANA_SEGUNDOS || $firma === '') {
            return $this->rechazar($request);
        }

        $esperada = hash_hmac('sha256', self::cadena($request, $fecha), $secreto);
        if (!hash_equals($esperada, $firma)) {
            return $this->rechazar($request);
        }

        return $next($request);
    }

    /** Cadena firmada. El cliente de Central (FrontofficeBandejaService) la monta igual. */
    public static function cadena(Request $request, string $fecha): string
    {
        $query = $request->query();
        ksort($query);

        return implode("\n", [
            $fecha,
            strtoupper($request->method()),
            '/'.ltrim($request->path(), '/'),
            http_build_query($query),
            (string) $request->header('X-DuaLab-Usuario', ''),
            $request->getContent(),
        ]);
    }

    private function rechazar(Request $request): Response
    {
        Log::warning('API de Central con firma no válida o caducada.', ['ip' => $request->ip()]);

        return response()->json(['error' => 'Firma no válida.'], 401);
    }
}
