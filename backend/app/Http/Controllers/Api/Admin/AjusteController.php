<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAjustesRequest;
use App\Jobs\EnviarFormularioAWebhook;
use App\Models\Ajuste;
use App\Services\ActividadService;
use App\Models\Contacto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Ajustes del sitio por grupo: seo (editores), analitica e integraciones (admin).
 * Los secretos nunca vuelven al navegador: solo si están configurados.
 */
class AjusteController extends Controller
{
    private const GRUPOS = ['seo', 'analitica', 'integraciones', 'apariencia'];

    private const DESCRIPCIONES = [
        'seo'           => 'cambió los ajustes generales de SEO',
        'analitica'     => 'cambió la configuración de analítica',
        'integraciones' => 'cambió las integraciones de los formularios',
        'apariencia'    => 'aplicó cambios de apariencia en la web',
    ];

    public function __construct(private ActividadService $actividad) {}

    public function show(string $grupo): JsonResponse
    {
        $this->autorizar($grupo);

        $ajustes = collect(config('cms.ajustes'))
            ->filter(fn ($d, $k) => str_starts_with($k, "$grupo."))
            ->mapWithKeys(fn ($def, $clave) => [
                substr($clave, strlen($grupo) + 1) => ! empty($def['secreto'])
                    ? ['configurado' => Ajuste::leer($clave) !== null]
                    : Ajuste::leer($clave),
            ]);

        return response()->json($ajustes);
    }

    public function update(UpdateAjustesRequest $request, string $grupo): JsonResponse
    {
        $this->autorizar($grupo);

        foreach ($request->validated('ajustes') as $nombre => $valor) {
            $clave = "$grupo.$nombre";
            // Secreto vacío = no tocar (el panel no conoce el valor actual).
            if (! empty(Ajuste::definicion($clave)['secreto'] ?? null) && ($valor === null || $valor === '')) {
                continue;
            }
            Ajuste::guardar($clave, $valor);
        }
        Cache::forget('web.configuracion');
        $this->actividad->registrar($request->user(), $grupo === 'integraciones' ? 'integraciones' : $grupo, 'editar', self::DESCRIPCIONES[$grupo]);

        return $this->show($grupo);
    }

    /** Quita un secreto guardado (p. ej. una API key que ya no se usa). */
    public function borrarSecreto(Request $request, string $grupo, string $nombre): JsonResponse
    {
        $this->autorizar($grupo);
        $clave = "$grupo.$nombre";
        abort_unless(! empty(Ajuste::definicion($clave)['secreto'] ?? null), 404);

        Ajuste::guardar($clave, null);
        $this->actividad->registrar($request->user(), 'integraciones', 'eliminar', 'quitó una clave guardada de '.$grupo);

        return $this->show($grupo);
    }

    /** Envía un formulario de ejemplo al webhook para comprobar la conexión. */
    public function probarWebhook(): JsonResponse
    {
        Gate::authorize('administrar-web');

        $url = Ajuste::leer('integraciones.webhook_url');
        abort_unless($url, 422, 'Primero guarda la URL del webhook.');

        $ejemplo = new Contacto([
            'nombre' => 'Prueba', 'apellidos' => 'DuaLab', 'email' => 'prueba@ejemplo.com',
            'tipo' => 'empresa', 'accion' => 'info', 'pagina_origen' => '/contacto',
        ]);
        $ejemplo->created_at = now();
        $cuerpo  = json_encode(['prueba' => true] + EnviarFormularioAWebhook::payload($ejemplo), JSON_UNESCAPED_UNICODE);
        $secreto = Ajuste::leer('integraciones.webhook_secreto');

        try {
            $respuesta = Http::timeout(8)
                ->withHeaders(array_filter(['X-DuaLab-Firma' => $secreto ? hash_hmac('sha256', $cuerpo, $secreto) : null]))
                ->withBody($cuerpo, 'application/json')
                ->post($url);
        } catch (\Throwable $e) {
            Log::warning('Prueba de webhook de formularios fallida', ['error' => class_basename($e)]);

            return response()->json(['ok' => false, 'message' => 'No se pudo conectar con la URL.'], 422);
        }

        return response()->json([
            'ok'      => $respuesta->successful(),
            'message' => $respuesta->successful()
                ? 'Conexión correcta: el destino respondió '.$respuesta->status().'.'
                : 'El destino respondió con error '.$respuesta->status().'.',
        ], $respuesta->successful() ? 200 : 422);
    }

    private function autorizar(string $grupo): void
    {
        abort_unless(in_array($grupo, self::GRUPOS, true), 404);
        Gate::authorize($grupo === 'seo' ? 'gestionar-web' : 'administrar-web');
    }
}
