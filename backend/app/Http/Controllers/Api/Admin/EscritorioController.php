<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\EntradaResource;
use App\Models\Ajuste;
use App\Models\Entrada;
use App\Models\Idioma;
use App\Models\Medio;
use App\Models\Pagina;
use Illuminate\Http\JsonResponse;

/** Escritorio del panel: resumen de cada módulo y lo último que ha llegado. */
class EscritorioController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            // Sin formularios: se atienden en DuaLab Central («Primer contacto»), no en Gestión web
            'entradas' => [
                'publicadas' => Entrada::visibles()->count(),
                'borradores' => Entrada::where('estado', 'borrador')->count(),
                'pendientes' => Entrada::where('estado', 'pendiente')->count(),
                'ultimas'    => EntradaResource::collection(Entrada::with('categoria')->latest('updated_at')->take(5)->get()),
            ],
            'paginas' => [
                'total'    => count(config('cms_paginas')),
                'editadas' => Pagina::has('contenidos')->count(),
            ],
            'medios' => [
                'total'  => Medio::count(),
                'tamano' => (int) Medio::sum('tamano'),
            ],
            'idiomas' => Idioma::activos()->pluck('nombre', 'codigo'),
            'configuracion' => [
                'seo'          => (bool) Ajuste::leer('seo.descripcion'),
                'analitica'    => (bool) (Ajuste::leer('analitica.ga4_id') || Ajuste::leer('analitica.gtm_id')),
                'integraciones'=> (bool) Ajuste::leer('integraciones.webhook_activo'),
            ],
        ]);
    }
}
