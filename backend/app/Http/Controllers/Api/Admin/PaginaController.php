<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePaginaRequest;
use App\Models\Idioma;
use App\Services\ActividadService;
use App\Services\PaginaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaginaController extends Controller
{
    public function __construct(private PaginaService $paginas, private ActividadService $actividad) {}

    public function index(): JsonResponse
    {
        return response()->json($this->paginas->listado());
    }

    public function show(Request $request, string $clave): JsonResponse
    {
        abort_unless($this->paginas->existe($clave), 404);
        $idioma = $request->validate(['idioma' => ['nullable', Rule::in(Idioma::pluck('codigo'))]])['idioma']
            ?? Idioma::codigoPorDefecto();

        return response()->json($this->paginas->detalle($clave, $idioma));
    }

    public function update(UpdatePaginaRequest $request, string $clave): JsonResponse
    {
        $idioma = $request->validated('idioma');
        $antes  = $this->paginas->foto($clave, $idioma);
        $this->paginas->guardar($clave, $request->validated(), $request->user());
        $despues = $this->paginas->foto($clave, $idioma);

        $this->actividad->registrar($request->user(), 'paginas', 'editar',
            'editó la página '.ActividadService::cita(config('cms_paginas')[$clave]['titulo'] ?? $clave)
            .($idioma !== Idioma::codigoPorDefecto() ? " ($idioma)" : ''), 'pagina', $clave,
            ActividadService::cambios($antes['valores'], $despues['valores'], $antes['etiquetas']));

        return response()->json($this->paginas->detalle($clave, $request->validated('idioma')));
    }
}
