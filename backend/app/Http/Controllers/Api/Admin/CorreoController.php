<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EnviarCorreoRequest;
use App\Http\Requests\Admin\ListarCorreosRequest;
use App\Http\Requests\Admin\UpdateCorreoRequest;
use App\Http\Resources\Admin\CorreoResource;
use App\Models\CorreoAdjunto;
use App\Models\CorreoEnvio;
use App\Models\CorreoMensaje;
use App\Services\ActividadService;
use App\Services\BandejaCorreoService;
use App\Services\CorreoEnvioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Bandeja de Correo (solo admin): copia local de info@ que llena `correo:sincronizar`. */
class CorreoController extends Controller
{
    public function __construct(
        private BandejaCorreoService $bandeja,
        private CorreoEnvioService $envios,
        private ActividadService $actividad,
    ) {}

    private const ESTADOS = ['nuevo' => 'no leído', 'leido' => 'leído', 'archivado' => 'archivado'];

    public function index(ListarCorreosRequest $request): AnonymousResourceCollection
    {
        $f = $request->validated();

        $pagina = $this->bandeja->listar($f, min((int) ($f['per_page'] ?? 25), 100));

        return CorreoResource::collection($pagina)
            ->additional([
                'resumen'    => $this->bandeja->resumen($f['buzon'] ?? 'info'),
                'salida'     => $this->bandeja->salida($f['buzon'] ?? 'info'),
                'remitentes' => $this->bandeja->remitentesConocidos($pagina->getCollection()),
            ]);
    }

    /** Lo deja en la bandeja de salida; sale por la cola (202: aún no se ha enviado). */
    public function enviar(EnviarCorreoRequest $request): JsonResponse
    {
        $envio = $this->envios->crear($request->user(), $request->validated(), $request->file('adjuntos', []));
        // Sin destinatarios ni asunto en el registro: el envío se cita por su número
        $this->actividad->registrar($request->user(), 'correo', $envio->tipo, "envió un correo (envío #{$envio->id})", 'correo_envio', $envio->id);

        return response()->json(['id' => $envio->id, 'estado' => $envio->estado], 202);
    }

    public function reintentar(Request $request, CorreoEnvio $envio): JsonResponse
    {
        $this->envios->reintentar($envio);
        $this->actividad->registrar($request->user(), 'correo', 'reintentar', "reintentó el envío #{$envio->id}", 'correo_envio', $envio->id);

        return response()->json(['id' => $envio->id, 'estado' => $envio->estado], 202);
    }

    public function descartar(Request $request, CorreoEnvio $envio): JsonResponse
    {
        $this->envios->descartar($envio);
        $this->actividad->registrar($request->user(), 'correo', 'descartar', "descartó el envío fallido #{$envio->id}", 'correo_envio', $envio->id);

        return response()->json(null, 204);
    }

    /** Abrir un correo lo marca como leído (solo en el panel). */
    public function show(CorreoMensaje $correo): CorreoResource
    {
        $this->bandeja->marcarLeido($correo);

        return new CorreoResource($correo->load('adjuntos'));
    }

    public function update(UpdateCorreoRequest $request, CorreoMensaje $correo): CorreoResource
    {
        $this->bandeja->cambiarEstado($correo, $request->validated('estado'));
        // Sin remitente ni asunto en el registro: el correo se cita por su número
        $this->actividad->registrar($request->user(), 'correo', 'estado',
            "marcó el correo #{$correo->id} como ".self::ESTADOS[$correo->estado], 'correo', $correo->id);

        return new CorreoResource($correo);
    }

    public function adjunto(CorreoMensaje $correo, CorreoAdjunto $adjunto): StreamedResponse
    {
        return $this->bandeja->descargar($adjunto);
    }
}
