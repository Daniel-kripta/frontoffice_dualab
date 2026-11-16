<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListarUsuariosRequest;
use App\Http\Requests\Admin\StoreUsuarioRequest;
use App\Http\Requests\Admin\UpdateUsuarioRequest;
use App\Http\Resources\Admin\UsuarioResource;
use App\Models\User;
use App\Services\UsuarioPanelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** Usuarios del panel (solo admin, ver routes/api.php). Lógica en UsuarioPanelService. */
class UsuarioController extends Controller
{
    public function __construct(private UsuarioPanelService $usuarios) {}

    public function index(ListarUsuariosRequest $request): AnonymousResourceCollection
    {
        $f = $request->validated();

        $consulta = User::query()
            ->whereIn('role', config('cms.roles_panel'))
            ->when($f['papelera'] ?? false, fn ($q) => $q->onlyTrashed())
            ->when($f['rol'] ?? null, fn ($q, $r) => $q->where('role', $r))
            ->when($f['buscar'] ?? null, function ($q, $t) {
                $like = '%'.addcslashes($t, '%_\\').'%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->orderBy('name');

        return UsuarioResource::collection($consulta->paginate(min((int) ($f['per_page'] ?? 50), 100)))
            ->additional([
                'roles'    => config('cms.nombres_roles'),
                'papelera' => User::onlyTrashed()->whereIn('role', config('cms.roles_panel'))->count(),
            ]);
    }

    public function store(StoreUsuarioRequest $request): JsonResponse
    {
        $usuario = $this->usuarios->crear($request->validated(), $request->user());

        return (new UsuarioResource($usuario))->response()->setStatusCode(201);
    }

    public function update(UpdateUsuarioRequest $request, User $usuario): UsuarioResource
    {
        return new UsuarioResource($this->usuarios->actualizar($usuario, $request->validated(), $request->user()));
    }

    public function bloquear(Request $request, User $usuario): UsuarioResource
    {
        return new UsuarioResource($this->usuarios->bloquear($usuario, true, $request->user()));
    }

    public function desbloquear(Request $request, User $usuario): UsuarioResource
    {
        return new UsuarioResource($this->usuarios->bloquear($usuario, false, $request->user()));
    }

    public function activar(Request $request, User $usuario): UsuarioResource
    {
        return new UsuarioResource($this->usuarios->activar($usuario, $request->user()));
    }

    public function reenviarCorreo(Request $request, User $usuario): JsonResponse
    {
        $this->usuarios->reenviarCorreo($usuario, $request->user());

        return response()->json(['message' => 'Correo de acceso enviado de nuevo.']);
    }

    public function destroy(Request $request, User $usuario): JsonResponse
    {
        $this->usuarios->eliminar($usuario, $request->user());

        return response()->json(['message' => 'Cuenta enviada a la papelera.']);
    }

    public function restaurar(Request $request, int $id): UsuarioResource
    {
        return new UsuarioResource($this->usuarios->restaurar(User::onlyTrashed()->findOrFail($id), $request->user()));
    }

    public function destruir(Request $request, int $id): JsonResponse
    {
        $this->usuarios->destruir(User::onlyTrashed()->findOrFail($id), $request->user());

        return response()->json(['message' => 'Cuenta eliminada definitivamente.']);
    }
}
