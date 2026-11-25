<?php

namespace App\Http\Controllers\Api\Integraciones;

use App\Http\Controllers\Api\Integraciones\Concerns\RegistraActividadCentral;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListarCorreosRequest;
use App\Http\Requests\Admin\UpdateCorreoRequest;
use App\Http\Requests\Integraciones\EnviarCorreoCentralRequest;
use App\Http\Resources\Admin\CorreoResource;
use App\Models\CorreoAdjunto;
use App\Models\CorreoEnvio;
use App\Models\CorreoMensaje;
use App\Services\BandejaCorreoService;
use App\Services\CorreoEnvioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Correo de info@ para DuaLab Central («Primer contacto»), por la API firmada (VerificarFirmaCentral).
 * Misma lógica que el Correo de Gestión web: la copia local la llena `correo:sincronizar` y los envíos
 * salen por la cola con el SMTP de info@. Central solo es la pantalla.
 */
class CentralCorreoController extends Controller
{
    use RegistraActividadCentral;

    private const ESTADOS = ['nuevo' => 'no leído', 'leido' => 'leído', 'archivado' => 'archivado', 'papelera' => 'en la papelera'];

    public function __construct(private BandejaCorreoService $bandeja, private CorreoEnvioService $envios) {}

    public function index(ListarCorreosRequest $request): AnonymousResourceCollection
    {
        $f = $request->validated();
        $pagina = $this->bandeja->listar($f, min((int) ($f['per_page'] ?? 25), 100));

        return CorreoResource::collection($pagina)->additional([
            'resumen'    => $this->bandeja->resumen($f['buzon'] ?? 'info', $f),
            'salida'     => $this->bandeja->salida($f['buzon'] ?? 'info'),
            'remitentes' => $this->bandeja->remitentesConocidos($pagina->getCollection()),
        ]);
    }

    /** Abrir un correo lo marca como leído (solo en la copia local, como en Gestión web). */
    public function show(CorreoMensaje $correo): CorreoResource
    {
        $this->bandeja->marcarLeido($correo);

        return new CorreoResource($correo->load('adjuntos'));
    }

    public function update(UpdateCorreoRequest $request, CorreoMensaje $correo): CorreoResource
    {
        $this->bandeja->cambiarEstado($correo, $request->validated('estado'));
        $this->registrarCentral($request, 'correo', 'estado', "marcó el correo #{$correo->id} como ".self::ESTADOS[$correo->estado], 'correo', $correo->id);

        return new CorreoResource($correo->load('adjuntos'));
    }

    /** Borrar del todo la copia local de un correo que ya está en la papelera (el original sigue en el webmail). */
    public function destroy(Request $request, CorreoMensaje $correo): JsonResponse
    {
        if ($correo->estado !== 'papelera') {
            return response()->json(['message' => 'Solo se borra del todo lo que está en la papelera.'], 422);
        }
        $id = $correo->id;
        $correo->load('adjuntos')->delete();
        $this->registrarCentral($request, 'correo', 'borrar', "borró del todo el correo #{$id}", 'correo', $id);

        return response()->json(null, 204);
    }

    public function vaciarPapelera(Request $request): JsonResponse
    {
        $buzon = $request->validate(['buzon' => ['nullable', \Illuminate\Validation\Rule::in(array_keys(config('correo.buzones')))]])['buzon'] ?? 'info';
        $n = $this->bandeja->vaciarPapelera($buzon);
        $this->registrarCentral($request, 'correo', 'vaciar', "vació la papelera del correo ({$n})", 'correo', 0);

        return response()->json(['eliminados' => $n]);
    }

    public function adjunto(CorreoMensaje $correo, CorreoAdjunto $adjunto): StreamedResponse
    {
        return $this->bandeja->descargar($adjunto);
    }

    /** Lo deja en la bandeja de salida; sale por la cola (202: aún no se ha enviado). */
    public function enviar(EnviarCorreoCentralRequest $request): JsonResponse
    {
        $envio = $this->envios->crear(null, $request->validated(), $request->file('adjuntos', []));
        $this->registrarCentral($request, 'correo', $envio->tipo, "envió un correo (envío #{$envio->id})", 'correo_envio', $envio->id);

        return response()->json(['id' => $envio->id, 'estado' => $envio->estado], 202);
    }

    /**
     * Un envío tal como salió (para el Historial de Central: «qué envió»). Se lee en el momento;
     * Central no lo copia. Los envíos se guardan 4 meses (CorreoEnvio::prunable).
     */
    public function envio(CorreoEnvio $envio): JsonResponse
    {
        $envio->load('adjuntos');

        return response()->json([
            'id'         => $envio->id,
            'tipo'       => $envio->tipo,
            'para'       => $envio->para ?? [],
            'cc'         => $envio->cc ?? [],
            'cco'        => $envio->cco ?? [],
            'asunto'     => $envio->asunto,
            'texto'      => $envio->texto,
            'estado'     => $envio->estado,
            'enviado_en' => $envio->enviado_en?->toIso8601String(),
            'creado_en'  => $envio->created_at?->toIso8601String(),
            'adjuntos'   => $envio->adjuntos->map(fn ($a) => ['nombre' => $a->nombre, 'tamano' => $a->tamano])->values(),
        ]);
    }

    public function reintentar(Request $request, CorreoEnvio $envio): JsonResponse
    {
        $this->envios->reintentar($envio);
        $this->registrarCentral($request, 'correo', 'reintentar', "reintentó el envío #{$envio->id}", 'correo_envio', $envio->id);

        return response()->json(['id' => $envio->id, 'estado' => $envio->estado], 202);
    }

    public function descartar(Request $request, CorreoEnvio $envio): JsonResponse
    {
        $this->envios->descartar($envio);
        $this->registrarCentral($request, 'correo', 'descartar', "descartó el envío fallido #{$envio->id}", 'correo_envio', $envio->id);

        return response()->json(null, 204);
    }
}
