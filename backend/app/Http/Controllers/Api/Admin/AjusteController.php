<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProbarWebhookRequest;
use App\Http\Requests\Admin\UpdateAjustesRequest;
use App\Jobs\EnviarFormularioAWebhook;
use App\Models\Ajuste;
use App\Services\ActividadService;
use App\Services\VisibilidadService;
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

    // Nombres en el historial (lo que se ve en el panel)
    private const ETIQUETAS = [
        'seo.titulo_sitio' => 'Nombre del sitio', 'seo.separador' => 'Separador', 'seo.descripcion' => 'Descripción por defecto',
        'seo.imagen_og' => 'Imagen para redes', 'seo.indexar' => 'Buscadores pueden mostrar la web', 'seo.search_console' => 'Código de Search Console',
        'seo.org_nombre' => 'Organización · Nombre', 'seo.org_logo' => 'Organización · Logo', 'seo.org_email' => 'Organización · Correo',
        'seo.org_telefono' => 'Organización · Teléfono', 'seo.org_direccion' => 'Organización · Dirección', 'seo.org_codigo_postal' => 'Organización · Código postal',
        'seo.org_localidad' => 'Organización · Localidad', 'seo.org_provincia' => 'Organización · Provincia', 'seo.org_redes' => 'Organización · Redes sociales',
        'seo.ia_bloqueados' => 'IA bloqueadas', 'seo.llms_resumen' => 'Presentación para las IA',
        'apariencia.paleta' => 'Paleta', 'apariencia.color_principal' => 'Color principal', 'apariencia.color_noche' => 'Color oscuro',
        'apariencia.color_titulos' => 'Color de los títulos', 'apariencia.fuente_titulos' => 'Fuente de los títulos', 'apariencia.fuente_texto' => 'Fuente del texto',
        'apariencia.tamano_texto' => 'Tamaño del texto', 'apariencia.logo' => 'Logo', 'apariencia.favicon' => 'Icono de la pestaña',
        'analitica.ga4_id' => 'ID de Google Analytics', 'analitica.gtm_id' => 'ID de Tag Manager', 'analitica.activa' => 'Analítica activa',
        'integraciones.crm_url' => 'CRM externo · Dirección', 'integraciones.crm_activo' => 'CRM externo · Activo', 'integraciones.crm_secreto' => 'CRM externo · Clave',
        'integraciones.brevo_api_key' => 'Clave de Brevo', 'integraciones.brevo_lista_id' => 'Lista de Brevo',
        'integraciones.mailchimp_api_key' => 'Clave de Mailchimp', 'integraciones.mailchimp_audiencia' => 'Audiencia de Mailchimp',
    ];

    public function __construct(private ActividadService $actividad) {}

    /** Valores del grupo, para el «antes → después» del historial. */
    private function foto(string $grupo): array
    {
        return collect(config('cms.ajustes'))->filter(fn ($d, $k) => str_starts_with($k, "$grupo."))
            ->map(fn ($d, $clave) => Ajuste::leer($clave))->all();
    }

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

        // Aviso a la app DuaLab: configurado en el servidor; el panel solo enseña si está conectado
        if ($grupo === 'integraciones') {
            $app = EnviarFormularioAWebhook::destino('webhook');
            $ajustes['app_dualab'] = ['conectada' => (bool) $app, 'servidor' => $app ? parse_url($app['url'], PHP_URL_HOST) : null];
        }

        return response()->json($ajustes);
    }

    public function update(UpdateAjustesRequest $request, string $grupo): JsonResponse
    {
        $this->autorizar($grupo);
        $antes = $this->foto($grupo);

        foreach ($request->validated('ajustes') as $nombre => $valor) {
            $clave = "$grupo.$nombre";
            // Secreto vacío = no tocar (el panel no conoce el valor actual).
            if (! empty(Ajuste::definicion($clave)['secreto'] ?? null) && ($valor === null || $valor === '')) {
                continue;
            }
            Ajuste::guardar($clave, $valor);
        }
        Cache::forget('web.configuracion');
        VisibilidadService::olvidarCache();
        // Los secretos nunca se copian al historial: solo que cambiaron
        $secretos = collect(config('cms.ajustes'))->filter(fn ($d) => ! empty($d['secreto']))->keys()->all();
        $etiquetas = collect(self::ETIQUETAS)->filter(fn ($e, $k) => str_starts_with($k, "$grupo."))->all();
        $this->actividad->registrar($request->user(), $grupo === 'integraciones' ? 'integraciones' : $grupo, 'editar', self::DESCRIPCIONES[$grupo],
            null, null, ActividadService::cambios($antes, $this->foto($grupo), $etiquetas, $secretos));

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

    /** Envía un formulario de ejemplo (marcado como prueba) al destino elegido: la app o el CRM. */
    public function probarWebhook(ProbarWebhookRequest $request): JsonResponse
    {
        $destino = EnviarFormularioAWebhook::destino($request->validated('destino') ?? 'webhook', soloActivo: false);
        abort_unless($destino, 422, ($request->validated('destino') ?? 'webhook') === 'webhook'
            ? 'El aviso a la app DuaLab no está configurado en el servidor.' : 'Primero guarda la dirección.');
        ['url' => $url, 'secreto' => $secreto] = $destino;

        $ejemplo = new Contacto([
            'nombre' => 'Prueba', 'apellidos' => 'DuaLab', 'email' => 'prueba@ejemplo.com',
            'tipo' => 'empresa', 'accion' => 'info', 'pagina_origen' => '/contacto',
        ]);
        $ejemplo->created_at = now();
        $cuerpo  = json_encode(['prueba' => true] + EnviarFormularioAWebhook::payload($ejemplo), JSON_UNESCAPED_UNICODE);
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
