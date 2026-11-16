<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Notifications\AccountBlockedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const MAX_FAILED_ATTEMPTS = 5;

    public function login(LoginRequest $request): JsonResponse
    {
        $user          = User::where('email', $request->email)->first();
        $validPassword = $user && Hash::check($request->password, $user->password);

        if (! $validPassword || $user->blocked) {
            if ($user && ! $validPassword && ! $user->blocked) {
                $user->increment('failed_login_attempts');

                if ($user->failed_login_attempts >= self::MAX_FAILED_ATTEMPTS) {
                    $user->forceFill(['blocked' => true])->save();
                    $user->notify(new AccountBlockedNotification());
                    Log::warning('Cuenta bloqueada por intentos fallidos', ['user_id' => $user->id]);
                }
            }

            // Sin PII en el log: email enmascarado e IP
            Log::notice('Login fallido', ['email' => self::enmascarar($request->email), 'ip' => $request->ip()]);

            throw ValidationException::withMessages([
                'email' => ['Las credenciales no son correctas.'],
            ]);
        }

        // Solo se llega aquí con la contraseña correcta: decirlo no enumera usuarios.
        if (! $user->email_verified_at) {
            throw ValidationException::withMessages([
                'email' => [$user->esDelPanel()
                    ? 'Esta cuenta aún no está activada. Pide a quien administra el panel que la active o usa el enlace del correo de acceso.'
                    : 'Esta cuenta aún no tiene el email verificado.'],
            ]);
        }

        // forceFill: failed_login_attempts no está en $fillable (con update() no se reiniciaba)
        $user->forceFill(['failed_login_attempts' => 0, 'ultimo_acceso_en' => now()])->save();

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['user' => new UserResource($user)]);
    }

    /** «cynthia@dualab.es» → «c***@dualab.es» */
    private static function enmascarar(?string $email): string
    {
        [$local, $dominio] = array_pad(explode('@', (string) $email, 2), 2, '');

        return mb_substr($local, 0, 1).'***@'.$dominio;
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(new UserResource($request->user()->load('inscripciones')));
    }
}
