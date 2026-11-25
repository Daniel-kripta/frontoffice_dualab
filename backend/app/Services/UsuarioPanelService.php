<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\InvitacionGestionWeb;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

/**
 * Cuentas del panel de gestión web con la misma lógica que la gestión de usuarios de la app
 * DuaLab (AdminUserController): el admin crea la cuenta con una contraseña inicial y queda
 * «pendiente de activar» hasta que la activa; puede cambiar nombre, email, rol y contraseña,
 * bloquear y enviar a la papelera. Además se envía un correo de acceso con un enlace para
 * que la persona elija su propia contraseña (al usarlo, la cuenta queda activada).
 * Nadie se bloquea ni se borra a sí mismo, y siempre queda al menos un admin activo.
 */
class UsuarioPanelService
{
    public function __construct(private ActividadService $actividad) {}

    public function crear(array $datos, User $admin): User
    {
        $usuario = DB::transaction(function () use ($datos) {
            $usuario = User::create(['name' => $datos['name'], 'email' => $datos['email'], 'password' => Hash::make($datos['password'])]);
            // Campos sensibles fuera de $fillable. Sin activar hasta que el admin la active
            // (o la persona use el enlace del correo), como en la app DuaLab.
            $usuario->forceFill(['role' => $datos['role'], 'blocked' => false, 'email_verified_at' => null])->save();

            return $usuario;
        });
        if ($datos['enviar_correo'] ?? true) {
            $this->enviarCorreoAcceso($usuario);
        }
        $this->actividad->registrar($admin, 'usuarios', 'crear',
            'creó la cuenta de '.ActividadService::cita($usuario->name).' como '.$this->nombreRol($usuario->role), 'usuario', $usuario->id);

        return $usuario;
    }

    public function actualizar(User $usuario, array $datos, User $admin): User
    {
        $this->asegurarDelPanel($usuario);
        if (isset($datos['role']) && $datos['role'] !== $usuario->role) {
            $this->noSobreSiMismo($usuario, $admin, 'No puedes cambiar tu propio rol.');
            if ($usuario->role === 'admin') {
                $this->quedaOtroAdmin($usuario);
            }
        }

        $cambiaContrasena = ! empty($datos['password']);
        if ($cambiaContrasena) {
            $this->noSobreSiMismo($usuario, $admin, 'Tu propia contraseña se cambia desde «Mi perfil».');
        }

        $antes = $usuario->role;
        $foto  = ['name' => $usuario->name, 'email' => $usuario->email, 'role' => $this->nombreRol($usuario->role)];
        $usuario->fill(array_intersect_key($datos, array_flip(['name', 'email'])));
        if (isset($datos['role'])) {
            $usuario->forceFill(['role' => $datos['role']]);
        }
        if ($cambiaContrasena) {
            $usuario->password = Hash::make($datos['password']);
        }
        $usuario->save();

        if ($cambiaContrasena) {
            // Contraseña nueva → fuera sesiones y tokens en todos los dispositivos
            $this->cerrarSesiones($usuario);
            Log::info('Contraseña de usuario del panel cambiada por un admin', ['target_user_id' => $usuario->id, 'admin_id' => $admin->id]);
            $this->actividad->registrar($admin, 'usuarios', 'contrasena', 'cambió la contraseña de '.ActividadService::cita($usuario->name), 'usuario', $usuario->id);
        }

        // El correo de la cuenta no se copia al historial: solo que cambió
        $cambios = ActividadService::cambios($foto, ['name' => $usuario->name, 'email' => $usuario->email, 'role' => $this->nombreRol($usuario->role)],
            ['name' => 'Nombre', 'email' => 'Correo', 'role' => 'Rol'], ['email']);
        if ($antes !== $usuario->role) {
            $this->actividad->registrar($admin, 'usuarios', 'cambiar_rol',
                'cambió el rol de '.ActividadService::cita($usuario->name).' a '.$this->nombreRol($usuario->role), 'usuario', $usuario->id, $cambios);
        } elseif ($usuario->wasChanged(['name', 'email'])) {
            $this->actividad->registrar($admin, 'usuarios', 'editar', 'editó la cuenta de '.ActividadService::cita($usuario->name), 'usuario', $usuario->id, $cambios);
        }

        return $usuario;
    }

    public function bloquear(User $usuario, bool $bloquear, User $admin): User
    {
        $this->asegurarDelPanel($usuario);
        $this->noSobreSiMismo($usuario, $admin, 'No puedes bloquear tu propia cuenta.');
        if ($bloquear && $usuario->role === 'admin') {
            $this->quedaOtroAdmin($usuario);
        }

        $usuario->forceFill(['blocked' => $bloquear, 'failed_login_attempts' => 0])->save();
        if ($bloquear) {
            $this->cerrarSesiones($usuario);
        }
        $this->actividad->registrar($admin, 'usuarios', $bloquear ? 'bloquear' : 'desbloquear',
            ($bloquear ? 'bloqueó' : 'desbloqueó').' la cuenta de '.ActividadService::cita($usuario->name), 'usuario', $usuario->id);

        return $usuario;
    }

    /** Como «Activar» de la app DuaLab: a partir de ahora puede iniciar sesión. */
    public function activar(User $usuario, User $admin): User
    {
        $this->asegurarDelPanel($usuario);
        $usuario->forceFill(['email_verified_at' => $usuario->email_verified_at ?? now()])->save();
        $this->actividad->registrar($admin, 'usuarios', 'activar', 'activó la cuenta de '.ActividadService::cita($usuario->name), 'usuario', $usuario->id);

        return $usuario;
    }

    public function reenviarCorreo(User $usuario, User $admin): void
    {
        $this->asegurarDelPanel($usuario);
        if ($usuario->email_verified_at) {
            throw ValidationException::withMessages(['usuario' => ['Esta cuenta ya está activada: si ha olvidado la contraseña, que use «¿Has olvidado tu contraseña?».']]);
        }
        $this->enviarCorreoAcceso($usuario);
        $this->actividad->registrar($admin, 'usuarios', 'reenviar', 'reenvió el correo de acceso a '.ActividadService::cita($usuario->name), 'usuario', $usuario->id);
    }

    /** A la papelera: no puede entrar y se cierran sus sesiones. Se puede restaurar. */
    public function eliminar(User $usuario, User $admin): void
    {
        $this->asegurarDelPanel($usuario);
        $this->noSobreSiMismo($usuario, $admin, 'No puedes eliminar tu propia cuenta.');
        if ($usuario->role === 'admin') {
            $this->quedaOtroAdmin($usuario);
        }
        $this->cerrarSesiones($usuario);
        $usuario->delete();
        $this->actividad->registrar($admin, 'usuarios', 'eliminar', 'envió a la papelera la cuenta de '.ActividadService::cita($usuario->name), 'usuario', $usuario->id);
    }

    public function restaurar(User $usuario, User $admin): User
    {
        $this->asegurarDelPanel($usuario);
        $usuario->restore();
        $this->actividad->registrar($admin, 'usuarios', 'restaurar', 'restauró la cuenta de '.ActividadService::cita($usuario->name), 'usuario', $usuario->id);

        return $usuario;
    }

    /** Solo desde la papelera. Lo que hizo sigue en el registro de actividad con su nombre. */
    public function destruir(User $usuario, User $admin): void
    {
        $this->asegurarDelPanel($usuario);
        $nombre = $usuario->name;
        $usuario->tokens()->delete();
        DB::table('password_reset_tokens')->where('email', $usuario->email)->delete();
        $usuario->forceDelete();
        $this->actividad->registrar($admin, 'usuarios', 'destruir', 'eliminó definitivamente la cuenta de '.ActividadService::cita($nombre), 'usuario');
    }

    public function nombreRol(string $rol): string
    {
        return '«'.(config('cms.nombres_roles')[$rol] ?? $rol).'»';
    }

    private function enviarCorreoAcceso(User $usuario): void
    {
        $usuario->notify(new InvitacionGestionWeb(Password::broker('invitaciones')->createToken($usuario), $usuario->role));
    }

    private function cerrarSesiones(User $usuario): void
    {
        DB::table('sessions')->where('user_id', $usuario->id)->delete();
        $usuario->tokens()->delete();
    }

    /** Las cuentas antiguas de la web (alumno, empresa, centro) no se gestionan desde aquí. */
    private function asegurarDelPanel(User $usuario): void
    {
        abort_unless($usuario->esDelPanel(), 404);
    }

    private function noSobreSiMismo(User $usuario, User $admin, string $mensaje): void
    {
        if ($usuario->is($admin)) {
            throw ValidationException::withMessages(['usuario' => [$mensaje]]);
        }
    }

    private function quedaOtroAdmin(User $usuario): void
    {
        $otros = User::where('role', 'admin')->where('blocked', false)->whereKeyNot($usuario->id)->count();
        if ($otros === 0) {
            throw ValidationException::withMessages(['usuario' => ['Tiene que quedar al menos un admin activo.']]);
        }
    }
}
