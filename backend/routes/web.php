<?php

use App\Http\Controllers\Web\PaginaPublicaController;
use App\Http\Controllers\Web\VisibilidadController;
use Illuminate\Support\Facades\Route;

// Buscadores e IA: dualab.es/robots.txt, /llms.txt y /sitemap.xml (ver VisibilidadController)
Route::middleware('throttle:60,1,web-visibilidad')->withoutMiddleware([
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
    \Illuminate\Cookie\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
])->group(function () {
    Route::get('robots.txt',  [VisibilidadController::class, 'robots'])->name('visibilidad.robots');
    Route::get('llms.txt',    [VisibilidadController::class, 'llms'])->name('visibilidad.llms');
    Route::get('sitemap.xml', [VisibilidadController::class, 'sitemap'])->name('visibilidad.sitemap');

    // Web pública con su texto ya escrito (HtmlPublicoService). Las páginas del panel salen de
    // config/cms_paginas.php; las fichas (familia, reto) llevan solo la cabecera general.
    foreach (config('cms_paginas') as $def) {
        if (($def['ruta'] ?? null) && ! str_contains($def['ruta'], ':')) {
            Route::get($def['ruta'], PaginaPublicaController::class);
        }
    }
    Route::get('noticias/{slug}', PaginaPublicaController::class)->where('slug', '[a-z0-9\-]+')->name('web.noticia');
    Route::get('familias/{slug}', PaginaPublicaController::class)->where('slug', '[a-z0-9\-]+');
    Route::get('retos-destacados/{id}', PaginaPublicaController::class)->whereNumber('id');
    Route::get('retos-destacados/{id}/solicitar-demo', PaginaPublicaController::class)->whereNumber('id');
});
