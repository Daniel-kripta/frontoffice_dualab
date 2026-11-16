<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListarFormulariosRequest;
use App\Http\Requests\Admin\UpdateFormularioRequest;
use App\Http\Resources\Admin\FormularioResource;
use App\Models\Contacto;
use App\Models\Ajuste;
use App\Services\ActividadService;
use App\Services\BandejaFormulariosService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Bandeja de formularios recibidos (tabla `contactos`). */
class FormularioController extends Controller
{
    public function __construct(private BandejaFormulariosService $bandeja, private ActividadService $actividad) {}

    private const ESTADOS = ['nuevo' => 'nuevo', 'leido' => 'leído', 'archivado' => 'archivado'];

    public function index(ListarFormulariosRequest $request): AnonymousResourceCollection
    {
        $f = $request->validated();

        return FormularioResource::collection($this->bandeja->listar($f, min((int) ($f['per_page'] ?? 25), 100)))
            ->additional([
                'resumen' => $this->bandeja->resumen(),
                // Avisos de la bandeja: el buzón de correo y dónde se validan las captaciones
                'avisos'  => [
                    'buzon'     => config('cms.buzon'),
                    'consola'   => ['nombre' => config('cms.consola.nombre'), 'url' => config('cms.consola.url')],
                    'captacion' => [
                        'activa' => (bool) (Ajuste::leer('integraciones.webhook_activo') && Ajuste::leer('integraciones.webhook_url')),
                        'tipos'  => config('cms.tipos_captacion'),
                    ],
                ],
            ]);
    }

    public function exportar(ListarFormulariosRequest $request): StreamedResponse
    {
        $this->actividad->registrar($request->user(), 'formularios', 'exportar', 'exportó formularios a CSV');

        return $this->bandeja->exportar($request->validated());
    }

    /** Abrir un envío lo marca como leído. */
    public function show(Contacto $contacto): FormularioResource
    {
        $this->bandeja->marcarLeido($contacto);

        return new FormularioResource($contacto);
    }

    public function update(UpdateFormularioRequest $request, Contacto $contacto): FormularioResource
    {
        $this->bandeja->cambiarEstado($contacto, $request->validated('estado'));
        $this->actividad->registrar($request->user(), 'formularios', 'estado',
            "marcó el formulario #{$contacto->id} como ".(self::ESTADOS[$contacto->estado] ?? $contacto->estado), 'formulario', $contacto->id);

        return new FormularioResource($contacto);
    }

    public function destroy(Request $request, Contacto $contacto): JsonResponse
    {
        $contacto->delete();
        $this->actividad->registrar($request->user(), 'formularios', 'eliminar', "eliminó el formulario #{$contacto->id}", 'formulario', $contacto->id);

        return response()->json(['message' => 'Envío eliminado.']);
    }
}
