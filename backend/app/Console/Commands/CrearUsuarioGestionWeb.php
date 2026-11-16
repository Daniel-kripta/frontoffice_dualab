<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Crea (o promueve) una cuenta con acceso al panel de gestión web.
 * La contraseña se pide por consola: nunca va en una migración ni en el repo.
 *
 *   php artisan gestion-web:usuario admin@dualab.es --rol=admin
 *   php artisan gestion-web:usuario admin@dualab.es --rol=admin --cambiar-contrasena
 */
class CrearUsuarioGestionWeb extends Command
{
    protected $signature = 'gestion-web:usuario {email} {--rol=editor : admin | editor | contactos} {--nombre=}
                            {--cambiar-contrasena : En una cuenta existente, pide una contraseña nueva}';

    protected $description = 'Crea o da acceso a una cuenta del panel de gestión web (admin, editor o contactos). Las demás cuentas, desde Usuarios en el panel';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));
        $rol   = $this->option('rol');

        if (! in_array($rol, config('cms.roles_panel'), true)) {
            $this->error('Rol no válido. Usa: '.implode(', ', config('cms.roles_panel')));

            return self::FAILURE;
        }
        if (Validator::make(['email' => $email], ['email' => 'required|email'])->fails()) {
            $this->error('Email no válido.');

            return self::FAILURE;
        }

        $usuario = User::where('email', $email)->first();

        if ($usuario) {
            if (! $this->confirm("La cuenta ya existe (rol actual: {$usuario->role}). ¿Darle el rol «{$rol}»?", true)) {
                return self::SUCCESS;
            }
            // Quien ejecuta el comando en el servidor responde por la cuenta: queda verificada
            $usuario->forceFill([
                'role' => $rol, 'blocked' => false, 'failed_login_attempts' => 0,
                'email_verified_at' => $usuario->email_verified_at ?? now(),
            ])->save();
            $this->info("Rol actualizado: {$email} → {$rol}");

            if ($this->option('cambiar-contrasena')) {
                $password = $this->pedirContrasena();
                if ($password === null) {
                    return self::FAILURE;
                }
                $usuario->forceFill(['password' => Hash::make($password)])->save();
                // Contraseña nueva → fuera tokens y sesiones abiertas en otros dispositivos
                $usuario->tokens()->delete();
                DB::table('sessions')->where('user_id', $usuario->id)->delete();
                $this->info('Contraseña cambiada y sesiones anteriores cerradas.');
            }

            return self::SUCCESS;
        }

        $nombre = $this->option('nombre') ?: $this->ask('Nombre');
        if (Validator::make(['nombre' => $nombre], ['nombre' => 'required|string|max:120'])->fails()) {
            $this->error('Nombre no válido.');

            return self::FAILURE;
        }
        $password = $this->pedirContrasena();
        if ($password === null) {
            return self::FAILURE;
        }

        $usuario = User::create(['name' => $nombre, 'email' => $email, 'password' => Hash::make($password)]);
        $usuario->forceFill(['role' => $rol, 'email_verified_at' => now()])->save();

        $this->info("Cuenta creada: {$email} ({$rol}). Ya puede entrar en /gestion-web.");

        return self::SUCCESS;
    }

    /** Pide la contraseña dos veces y la valida con la política del proyecto. */
    private function pedirContrasena(): ?string
    {
        $password = $this->secret('Contraseña (mín. 8, mayúscula, minúscula, número y símbolo)');
        if ($password !== $this->secret('Repite la contraseña')) {
            $this->error('Las contraseñas no coinciden.');

            return null;
        }

        $validacion = Validator::make(
            ['password' => $password],
            ['password' => ['required', Password::min(8)->mixedCase()->numbers()->symbols()]],
        );
        if ($validacion->fails()) {
            foreach ($validacion->errors()->all() as $error) {
                $this->error($error);
            }

            return null;
        }

        return $password;
    }
}
