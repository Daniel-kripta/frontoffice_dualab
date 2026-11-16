<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePerfilRequest;
use App\Http\Resources\UserResource;
use App\Services\ActividadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Perfil propio en el panel (lógica reciclada de la app DuaLab, AdminAuthController::
 * getPerfil/updatePerfil): el email no cambia; nombre y contraseña sí. Al cambiar la
 * contraseña se cierran las sesiones del resto de dispositivos y se revocan los tokens.
 */
class PerfilController extends Controller
{
    public function show(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function update(UpdatePerfilRequest $request, ActividadService $actividad): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $user->name = $data['name'];
        $cambiaContrasena = ! empty($data['password']);
        if ($cambiaContrasena) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        if ($cambiaContrasena) {
            // Nunca la sesión propia: Laravel la volvería a guardar al final de esta petición
            DB::table('sessions')->where('user_id', $user->id)
                ->when($request->hasSession(), fn ($q) => $q->where('id', '!=', $request->session()->getId()))
                ->delete();
            $user->tokens()->delete();
            Log::info('Contraseña cambiada por el propio usuario (panel gestión web)', ['user_id' => $user->id]);
            $actividad->registrar($user, 'perfil', 'contrasena', 'cambió su contraseña', 'usuario', $user->id);
        }

        return response()->json([
            'password_changed' => $cambiaContrasena,
            'message'          => $cambiaContrasena
                ? 'Contraseña actualizada. Se ha cerrado la sesión en tus otros dispositivos.'
                : 'Perfil actualizado.',
            'user'             => new UserResource($user),
        ]);
    }
}
