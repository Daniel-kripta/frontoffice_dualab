<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreIdiomaRequest;
use App\Http\Requests\Admin\UpdateIdiomaRequest;
use App\Http\Resources\Admin\IdiomaResource;
use App\Models\Entrada;
use App\Models\Idioma;
use App\Services\ActividadService;
use App\Services\PaginaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class IdiomaController extends Controller
{
    public function __construct(private ActividadService $actividad) {}

    public function index(): AnonymousResourceCollection
    {
        $entradas = Entrada::selectRaw('idioma, count(*) as total')->groupBy('idioma')->pluck('total', 'idioma');
        $idiomas  = Idioma::orderByDesc('por_defecto')->orderBy('orden')->get()
            ->each(fn ($i) => $i->entradas_count = (int) ($entradas[$i->codigo] ?? 0));

        return IdiomaResource::collection($idiomas)->additional([
            'disponibles' => config('cms.idiomas_disponibles'),
        ]);
    }

    public function store(StoreIdiomaRequest $request): IdiomaResource
    {
        $codigo = $request->validated('codigo');
        $idioma = Idioma::create([
            'codigo' => $codigo,
            'nombre' => config("cms.idiomas_disponibles.$codigo"),
            'activo' => false, // se activa cuando haya textos traducidos
            'orden'  => (int) Idioma::max('orden') + 1,
        ]);
        $this->olvidarCaches();
        $this->actividad->registrar($request->user(), 'idiomas', 'crear', 'añadió el idioma '.$idioma->nombre.' (aún sin activar)');

        return new IdiomaResource($idioma);
    }

    public function update(UpdateIdiomaRequest $request, Idioma $idioma): IdiomaResource
    {
        $datos = $request->validated();

        DB::transaction(function () use ($idioma, $datos) {
            if (! empty($datos['por_defecto'])) {
                Idioma::where('por_defecto', true)->update(['por_defecto' => false]);
                $idioma->forceFill(['por_defecto' => true, 'activo' => true]);
            }
            // El idioma por defecto siempre está activo.
            if (array_key_exists('activo', $datos) && ! $idioma->por_defecto) {
                $idioma->activo = $datos['activo'];
            }
            if (array_key_exists('orden', $datos)) {
                $idioma->orden = $datos['orden'];
            }
            $idioma->save();
        });
        $this->olvidarCaches();
        $this->actividad->registrar($request->user(), 'idiomas', 'editar', match (true) {
            ! empty($datos['por_defecto']) => 'puso '.$idioma->nombre.' como idioma principal',
            $idioma->wasChanged('activo')  => ($idioma->activo ? 'activó' : 'desactivó').' el idioma '.$idioma->nombre,
            default                        => 'cambió el orden del idioma '.$idioma->nombre,
        });

        return new IdiomaResource($idioma);
    }

    public function destroy(Request $request, Idioma $idioma): JsonResponse
    {
        abort_if($idioma->por_defecto, 422, 'El idioma por defecto no se puede quitar.');
        abort_if(Entrada::withTrashed()->where('idioma', $idioma->codigo)->exists(), 422,
            'Hay entradas en este idioma: bórralas o desactiva el idioma en lugar de quitarlo.');

        $idioma->delete();
        $this->actividad->registrar($request->user(), 'idiomas', 'eliminar', 'quitó el idioma '.$idioma->nombre);
        $this->olvidarCaches();

        return response()->json(['message' => 'Idioma quitado.']);
    }

    private function olvidarCaches(): void
    {
        Cache::forget('web.configuracion');
        foreach (array_keys(config('cms_paginas')) as $clave) {
            app(PaginaService::class)->olvidarCache($clave);
        }
    }
}
