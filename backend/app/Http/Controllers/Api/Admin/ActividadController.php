<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListarActividadRequest;
use App\Http\Resources\Admin\ActividadResource;
use App\Models\ActividadPanel;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** Registro de actividad: el completo (solo admin) y el historial de una página o entrada. */
class ActividadController extends Controller
{
    private const HISTORIALES = ['pagina', 'entrada'];

    public function index(ListarActividadRequest $request): AnonymousResourceCollection
    {
        $f = $request->validated();

        $actividad = ActividadPanel::query()
            ->when($f['user_id'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($f['seccion'] ?? null, fn ($q, $s) => $q->where('seccion', $s))
            ->when($f['desde'] ?? null, fn ($q, $d) => $q->where('created_at', '>=', $d))
            ->when($f['hasta'] ?? null, fn ($q, $h) => $q->where('created_at', '<', now()->parse($h)->addDay()))
            ->latest('created_at')->latest('id')
            ->paginate(min((int) ($f['per_page'] ?? 50), 100));

        return ActividadResource::collection($actividad)->additional([
            'secciones' => ActividadPanel::SECCIONES,
            'personas'  => User::withTrashed()->whereIn('role', config('cms.roles_panel'))->orderBy('name')->take(200)->get(['id', 'name']),
        ]);
    }

    /** Historial de un elemento (lo ven quienes editan contenido). */
    public function historial(string $tipo, string $ref): AnonymousResourceCollection
    {
        abort_unless(in_array($tipo, self::HISTORIALES, true), 404);

        return ActividadResource::collection(
            ActividadPanel::where('objeto_tipo', $tipo)->where('objeto_ref', $ref)->latest('created_at')->latest('id')->take(30)->get()
        );
    }
}
