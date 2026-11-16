<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CrearContrasenaRequest;
use App\Http\Requests\OlvideContrasenaRequest;
use App\Models\User;
use App\Notifications\RestablecerContrasenaGestionWeb;
use App\Services\ActividadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Contraseñas del panel de gestión web sin sesión: crearla desde la invitación y
 * «¿Has olvidado tu contraseña?». Respuestas genéricas: nunca dicen si el email existe.
 */
class ContrasenaController extends Controller
{
    private const MENSAJE_OLVIDO = 'Si existe una cuenta del panel con ese email, recibirás un enlace para elegir una contraseña nueva.';

    public function olvide(OlvideContrasenaRequest $request): JsonResponse
    {
        $usuario = User::where('email', strtolower(trim($request->validated('email'))))->first();

        // Solo cuentas del panel no bloqueadas: un bloqueo lo levanta el admin, no un correo.
        // El broker limita a 1 enlace por email cada 5 min (config/auth.php)
        if ($usuario && $usuario->esDelPanel() && ! $usuario->blocked) {
            Password::broker('users')->sendResetLink(['email' => $usuario->email],
                fn (User $u, string $token) => $u->notify(new RestablecerContrasenaGestionWeb($token)));
        }

        return response()->json(['message' => self::MENSAJE_OLVIDO]);
    }

    public function crear(CrearContrasenaRequest $request, ActividadService $actividad): JsonResponse
    {
        $datos  = $request->validated();
        $broker = $datos['modo'] === 'invitacion' ? 'invitaciones' : 'users';
        $hecho  = null;
        $error  = response()->json(['message' => 'El enlace no es válido o ha caducado. Pide uno nuevo.'], 422);

        // Solo cuentas del panel (las antiguas de la web no usan este flujo)
        if (! User::where('email', strtolower(trim($datos['email'])))->first()?->esDelPanel()) {
            return $error;
        }

        $estado = Password::broker($broker)->reset(
            ['email' => strtolower(trim($datos['email'])), 'token' => $datos['token'], 'password' => $datos['password']],
            function (User $usuario, string $password) use (&$hecho) {
                // Usar el enlace del correo demuestra que el email es suyo
                $usuario->forceFill([
                    'password'              => Hash::make($password),
                    'remember_token'        => Str::random(60),
                    'email_verified_at'     => $usuario->email_verified_at ?? now(),
                    'failed_login_attempts' => 0,
                ])->save();
                $usuario->tokens()->delete();
                DB::table('sessions')->where('user_id', $usuario->id)->delete();
                $hecho = $usuario;
            }
        );

        if ($estado !== Password::PASSWORD_RESET || ! $hecho) {
            return $error;
        }

        $actividad->registrar($hecho, 'perfil', 'contrasena',
            $datos['modo'] === 'invitacion' ? 'aceptó la invitación y creó su contraseña' : 'restableció su contraseña', 'usuario', $hecho->id);

        return response()->json(['message' => 'Contraseña guardada. Ya puedes entrar en el panel.']);
    }
}
