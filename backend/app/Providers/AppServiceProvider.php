<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JsonResource::withoutWrapping();

        Gate::define('manage-retos', function (User $user) {
            return in_array($user->role, ['empresa', 'admin'], true);
        });

        // Panel de gestión web (/gestion-web): editores y administradores. Idiomas,
        // ajustes de analítica e integraciones, solo administradores.
        // Panel de gestión web: entrar (todos sus roles), contenido (admin y editor) y administración
        // (admin). Formularios y correo se atienden en DuaLab Central (API integraciones/central)
        Gate::define('entrar-panel', fn (User $user) => in_array($user->role, config('cms.roles_panel'), true));
        Gate::define('gestionar-web', fn (User $user) => in_array($user->role, config('cms.roles_contenido'), true));
        Gate::define('administrar-web', fn (User $user) => in_array($user->role, config('cms.roles_admin'), true));

        RateLimiter::for('login', function (Request $request) {
            $key = Str::lower((string) $request->input('email')).'|'.$request->ip();

            return Limit::perMinute(5)->by($key);
        });
    }
}
