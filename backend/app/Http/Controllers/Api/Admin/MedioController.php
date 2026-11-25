<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListarMediosRequest;
use App\Http\Requests\Admin\StoreMedioRequest;
use App\Http\Requests\Admin\UpdateMedioRequest;
use App\Http\Resources\Admin\MedioResource;
use App\Models\Medio;
use App\Services\ActividadService;
use App\Services\CloudinaryService;
use App\Services\MedioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class MedioController extends Controller
{
    public function __construct(private MedioService $medios, private ActividadService $actividad) {}

    public function index(ListarMediosRequest $request): AnonymousResourceCollection
    {
        $f = $request->validated();

        $medios = Medio::with('autor:id,name')
            ->when($f['tipo'] ?? null, fn ($q, $t) => $q->where('tipo', $t))
            ->when($f['buscar'] ?? null, function ($q, $t) {
                $like = '%'.addcslashes($t, '%_\\').'%';
                $q->where(fn ($w) => $w->where('titulo', 'like', $like)->orWhere('nombre_original', 'like', $like)->orWhere('alt', 'like', $like));
            })
            ->latest()
            ->paginate(min((int) ($f['per_page'] ?? 40), 100));

        return MedioResource::collection($medios)->additional(['uso' => MedioService::uso()]);
    }

    /**
     * Dónde se guardan los medios: conexión con Cloudinary (sin claves) y consumo de la cuenta.
     * Solo admin. Las claves viven en el .env del servidor, nunca en el panel.
     */
    public function almacenamiento(CloudinaryService $cloudinary): JsonResponse
    {
        Gate::authorize('administrar-web');

        return response()->json($cloudinary->estado());
    }

    /** Vuelve a consultar Cloudinary ahora (comprobar la conexión). */
    public function comprobarAlmacenamiento(CloudinaryService $cloudinary): JsonResponse
    {
        Gate::authorize('administrar-web');

        return response()->json($cloudinary->estado(refrescar: true));
    }

    public function store(StoreMedioRequest $request): MedioResource|JsonResponse
    {
        try {
            $medio = $this->medios->subir($request->file('archivo'), $request->user(), $request->validated('alt'));
        } catch (\RuntimeException $e) {
            return response()->json(['message' => 'No se ha podido subir el archivo al almacenamiento de medios. Inténtalo de nuevo.'], 502);
        }

        $this->actividad->registrar($request->user(), 'medios', 'subir', 'subió '.ActividadService::cita($medio->titulo), 'medio', $medio->id);

        return new MedioResource($medio);
    }

    public function update(UpdateMedioRequest $request, Medio $medio): MedioResource
    {
        $campos = ['titulo' => 'Título', 'alt' => 'Texto alternativo'];
        $antes  = $medio->only(array_keys($campos));
        $medio->update($request->validated());
        $this->actividad->registrar($request->user(), 'medios', 'editar', 'editó los datos de '.ActividadService::cita($medio->titulo), 'medio', $medio->id,
            ActividadService::cambios($antes, $medio->only(array_keys($campos)), $campos));

        return new MedioResource($medio);
    }

    public function destroy(Request $request, Medio $medio): JsonResponse
    {
        $this->medios->eliminar($medio);
        $this->actividad->registrar($request->user(), 'medios', 'eliminar', 'eliminó '.ActividadService::cita($medio->titulo), 'medio', $medio->id);

        return response()->json(['message' => 'Archivo eliminado.']);
    }
}
