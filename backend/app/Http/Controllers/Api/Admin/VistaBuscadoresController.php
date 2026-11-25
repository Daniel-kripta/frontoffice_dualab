<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VerComoBuscadoresRequest;
use App\Services\HtmlPublicoService;
use Illuminate\Http\JsonResponse;

/** «Cómo nos ven Google y la IA»: lo que recibe un rastreador al pedir una página de la web. */
class VistaBuscadoresController extends Controller
{
    public function __invoke(VerComoBuscadoresRequest $request, HtmlPublicoService $html): JsonResponse
    {
        return response()->json($html->analizar($request->validated('ruta'), $request->validated('idioma')));
    }
}
