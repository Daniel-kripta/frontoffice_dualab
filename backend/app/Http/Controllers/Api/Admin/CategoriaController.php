<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoriaRequest;
use App\Http\Requests\Admin\UpdateCategoriaRequest;
use App\Http\Resources\Admin\CategoriaResource;
use App\Models\Categoria;
use App\Services\ActividadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class CategoriaController extends Controller
{
    public function __construct(private ActividadService $actividad) {}

    public function index(): AnonymousResourceCollection
    {
        return CategoriaResource::collection(Categoria::withCount('entradas')->orderBy('nombre')->take(200)->get());
    }

    public function store(StoreCategoriaRequest $request): CategoriaResource
    {
        $categoria = new Categoria($request->validated());
        $categoria->slug = $this->slugUnico($categoria->nombre);
        $categoria->save();
        $this->actividad->registrar($request->user(), 'categorias', 'crear', 'creó la categoría '.ActividadService::cita($categoria->nombre));

        return new CategoriaResource($categoria);
    }

    public function update(UpdateCategoriaRequest $request, Categoria $categoria): CategoriaResource
    {
        $categoria->fill($request->validated());
        if ($categoria->isDirty('nombre')) {
            $categoria->slug = $this->slugUnico($categoria->nombre, $categoria->id);
        }
        $categoria->save();
        $this->actividad->registrar($request->user(), 'categorias', 'editar', 'editó la categoría '.ActividadService::cita($categoria->nombre));

        return new CategoriaResource($categoria);
    }

    /** Las entradas de la categoría se quedan sin categoría (nullOnDelete). */
    public function destroy(Request $request, Categoria $categoria): JsonResponse
    {
        $categoria->delete();
        $this->actividad->registrar($request->user(), 'categorias', 'eliminar', 'eliminó la categoría '.ActividadService::cita($categoria->nombre));

        return response()->json(['message' => 'Categoría eliminada.']);
    }

    private function slugUnico(string $nombre, ?int $ignorar = null): string
    {
        $base = Str::slug($nombre) ?: 'categoria';
        $slug = $base;
        $n    = 2;
        while (Categoria::where('slug', $slug)->when($ignorar, fn ($q) => $q->whereKeyNot($ignorar))->exists()) {
            $slug = "$base-".$n++;
        }

        return $slug;
    }
}
