<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DevolverEntradaRequest;
use App\Http\Requests\Admin\ListarEntradasRequest;
use App\Http\Requests\Admin\StoreEntradaRequest;
use App\Http\Requests\Admin\TraducirEntradaRequest;
use App\Http\Requests\Admin\UpdateEntradaRequest;
use App\Http\Resources\Admin\EntradaResource;
use App\Models\Entrada;
use App\Services\ActividadService;
use App\Services\SeoService;
use App\Services\EntradaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EntradaController extends Controller
{
    public function __construct(private EntradaService $entradas, private ActividadService $actividad) {}

    private const VERBOS_ESTADO = ['pendiente' => 'envió a revisión', 'publicada' => 'publicó', 'borrador' => 'pasó a borrador'];

    private function registrar(Request $request, string $accion, string $verbo, Entrada $entrada, ?array $detalle = null): void
    {
        $this->actividad->registrar($request->user(), 'entradas', $accion, $verbo.' la entrada '.ActividadService::cita($entrada->titulo), 'entrada', $entrada->id, $detalle);
    }

    private const ETIQUETAS = [
        'titulo' => 'Título', 'slug' => 'Dirección (URL)', 'extracto' => 'Extracto', 'contenido' => 'Contenido',
        'imagen_destacada' => 'Imagen destacada', 'categoria' => 'Categoría', 'estado' => 'Estado',
        'publicada_en' => 'Fecha de publicación', 'destacada' => 'Destacada',
    ];

    /** Lo que se ve de la entrada, para el «antes → después» del historial. */
    private function foto(Entrada $entrada): array
    {
        $entrada->loadMissing(['categoria', 'seo']);

        return $entrada->only(['titulo', 'slug', 'extracto', 'contenido', 'imagen_destacada', 'estado', 'publicada_en', 'destacada'])
            + ['categoria' => $entrada->categoria?->nombre]
            + collect(SeoService::ETIQUETAS)->keys()->mapWithKeys(fn ($c) => ["seo.$c" => $entrada->seo?->$c])->all();
    }

    private function cambios(array $antes, Entrada $entrada): array
    {
        $etiquetas = self::ETIQUETAS + collect(SeoService::ETIQUETAS)->mapWithKeys(fn ($e, $c) => ["seo.$c" => $e])->all();

        // El contenido es HTML largo: solo se apunta que cambió
        return ActividadService::cambios($antes, $this->foto($entrada->fresh(['categoria', 'seo'])), $etiquetas, ['contenido']);
    }

    public function index(ListarEntradasRequest $request): AnonymousResourceCollection
    {
        $f = $request->validated();

        $consulta = Entrada::with(['categoria', 'autor:id,name'])
            ->when(($f['estado'] ?? null) === 'papelera', fn ($q) => $q->onlyTrashed())
            ->when(($f['estado'] ?? null) === 'programada', fn ($q) => $q->where('estado', 'publicada')->where('publicada_en', '>', now()))
            ->when(($f['estado'] ?? null) === 'publicada', fn ($q) => $q->visibles())
            ->when(($f['estado'] ?? null) === 'borrador', fn ($q) => $q->where('estado', 'borrador'))
            ->when(($f['estado'] ?? null) === 'pendiente', fn ($q) => $q->where('estado', 'pendiente'))
            ->when($f['idioma'] ?? null, fn ($q, $i) => $q->where('idioma', $i))
            ->when($f['categoria_id'] ?? null, fn ($q, $c) => $q->where('categoria_id', $c))
            ->when($f['buscar'] ?? null, fn ($q, $t) => $q->where('titulo', 'like', '%'.addcslashes($t, '%_\\').'%'))
            ->latest('updated_at');

        return EntradaResource::collection($consulta->paginate(min((int) ($f['per_page'] ?? 20), 100)))
            ->additional(['contadores' => [
                'todas'      => Entrada::count(),
                'publicada'  => Entrada::visibles()->count(),
                'programada' => Entrada::where('estado', 'publicada')->where('publicada_en', '>', now())->count(),
                'pendiente'  => Entrada::where('estado', 'pendiente')->count(),
                'borrador'   => Entrada::where('estado', 'borrador')->count(),
                'papelera'   => Entrada::onlyTrashed()->count(),
            ]]);
    }

    public function store(StoreEntradaRequest $request): EntradaResource
    {
        $entrada = $this->entradas->crear($request->validated(), $request->user());
        $this->registrar($request, 'crear', match ($entrada->estado) {
            'pendiente' => 'creó y envió a revisión', 'publicada' => 'creó y publicó', default => 'creó',
        }, $entrada);

        return new EntradaResource($entrada->load(['categoria', 'seo']));
    }

    public function show(Entrada $entrada): EntradaResource
    {
        return (new EntradaResource($entrada->load(['categoria', 'seo', 'autor:id,name', 'aprobador:id,name'])))
            ->additional(['traducciones' => $entrada->traducciones()->get(['id', 'idioma', 'titulo', 'estado'])]);
    }

    public function update(UpdateEntradaRequest $request, Entrada $entrada): EntradaResource
    {
        $this->authorize('update', $entrada);
        $antes   = $entrada->estado;
        $foto    = $this->foto($entrada);
        $entrada = $this->entradas->actualizar($entrada, $request->validated(), $request->user());
        $cambio  = $antes !== $entrada->estado;
        $this->registrar($request, $cambio ? 'estado' : 'editar', $cambio ? self::VERBOS_ESTADO[$entrada->estado] : 'editó', $entrada, $this->cambios($foto, $entrada));

        return new EntradaResource($entrada->load(['categoria', 'seo']));
    }

    /** A la papelera (se puede restaurar). */
    public function destroy(Request $request, Entrada $entrada): JsonResponse
    {
        $this->authorize('delete', $entrada);
        $entrada->delete();
        $this->registrar($request, 'eliminar', 'movió a la papelera', $entrada);

        return response()->json(['message' => 'Entrada movida a la papelera.']);
    }

    public function restaurar(Request $request, int $id): EntradaResource
    {
        $entrada = Entrada::onlyTrashed()->findOrFail($id);
        $entrada->restore();
        $this->registrar($request, 'restaurar', 'restauró', $entrada);

        return new EntradaResource($entrada->load('categoria'));
    }

    public function eliminarDefinitivamente(Request $request, int $id): JsonResponse
    {
        $this->authorize('eliminarDefinitivamente', Entrada::class);
        $entrada = Entrada::onlyTrashed()->findOrFail($id);
        $entrada->seo()->delete();
        $entrada->forceDelete();
        $this->registrar($request, 'destruir', 'eliminó definitivamente', $entrada);

        return response()->json(['message' => 'Entrada eliminada definitivamente.']);
    }

    public function aprobar(Request $request, Entrada $entrada): EntradaResource
    {
        $this->authorize('aprobar', Entrada::class);

        $this->entradas->aprobar($entrada, $request->user());
        $this->registrar($request, 'aprobar', 'aprobó y publicó', $entrada);

        return new EntradaResource($entrada->load('categoria'));
    }

    public function devolver(DevolverEntradaRequest $request, Entrada $entrada): EntradaResource
    {
        $this->authorize('aprobar', Entrada::class);

        $this->entradas->devolver($entrada, $request->validated('nota'));
        $this->registrar($request, 'devolver', 'devolvió con una nota', $entrada,
            [['campo' => 'Nota para el autor', 'antes' => null, 'despues' => ActividadService::texto($request->validated('nota'))]]);

        return new EntradaResource($entrada->load('categoria'));
    }

    public function retirar(Request $request, Entrada $entrada): EntradaResource
    {
        $this->authorize('aprobar', Entrada::class);
        abort_unless($entrada->estado === 'publicada', 422, 'Solo se puede retirar una entrada publicada.');
        $this->entradas->retirar($entrada);
        $this->registrar($request, 'retirar', 'retiró de la web', $entrada);

        return new EntradaResource($entrada->load('categoria'));
    }

    public function traducir(TraducirEntradaRequest $request, Entrada $entrada): EntradaResource
    {
        $copia = $this->entradas->traducir($entrada, $request->validated('idioma'), $request->user());
        if ($copia->wasRecentlyCreated) {
            $this->registrar($request, 'traducir', 'creó la versión en '.$copia->idioma.' de', $copia);
        }

        return new EntradaResource($copia->load('categoria'));
    }
}
