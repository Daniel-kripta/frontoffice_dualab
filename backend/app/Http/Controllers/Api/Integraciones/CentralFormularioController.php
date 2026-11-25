<?php

namespace App\Http\Controllers\Api\Integraciones;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListarFormulariosRequest;
use App\Http\Requests\Admin\UpdateFormularioRequest;
use App\Http\Resources\Integraciones\FormularioCentralResource;
use App\Http\Controllers\Api\Integraciones\Concerns\RegistraActividadCentral;
use App\Models\Contacto;
use App\Services\BandejaFormulariosService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Bandeja de formularios para DuaLab Central (espacio «Primer contacto»), por la API firmada
 * (VerificarFirmaCentral). Misma lógica que la bandeja de Gestión web (BandejaFormulariosService):
 * Central solo traslada la pantalla; los datos y el correo siguen aquí.
 */
class CentralFormularioController extends Controller
{
    use RegistraActividadCentral;

    private const ESTADOS = ['nuevo' => 'nuevo', 'leido' => 'leído', 'archivado' => 'archivado'];

    public function __construct(private BandejaFormulariosService $bandeja) {}

    public function index(ListarFormulariosRequest $request): AnonymousResourceCollection
    {
        $f = $request->validated();

        return FormularioCentralResource::collection($this->bandeja->listar($f, min((int) ($f['per_page'] ?? 25), 100)))
            ->additional([
                'resumen'    => $this->bandeja->resumen(),
                'formularios' => BandejaFormulariosService::FORMULARIOS,
                'acciones'   => BandejaFormulariosService::ACCIONES,
            ]);
    }

    /** CSV para importar en un CRM (mismos filtros que la bandeja; fórmulas neutralizadas). */
    public function exportar(ListarFormulariosRequest $request): StreamedResponse
    {
        $this->registrarCentral($request, 'formularios', 'exportar', 'exportó formularios a CSV', 'formulario', 0);

        return $this->bandeja->exportar($request->validated());
    }

    /** Abrir un envío lo marca como leído, igual que en Gestión web. */
    public function show(Contacto $contacto): FormularioCentralResource
    {
        $this->bandeja->marcarLeido($contacto);

        return new FormularioCentralResource($contacto->load('respuestas.usuario'));
    }

    public function update(UpdateFormularioRequest $request, Contacto $contacto): FormularioCentralResource
    {
        $this->bandeja->cambiarEstado($contacto, $request->validated('estado'));
        $this->registrarCentral($request, 'formularios', 'estado', "marcó el formulario #{$contacto->id} como ".(self::ESTADOS[$contacto->estado] ?? $contacto->estado),
            'formulario', $contacto->id);

        return new FormularioCentralResource($contacto->load('respuestas.usuario'));
    }
}
