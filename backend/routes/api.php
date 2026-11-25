<?php

use App\Http\Controllers\Api\Integraciones\CentralCorreoController;
use App\Http\Controllers\Api\Integraciones\CentralFormularioController;
use App\Http\Middleware\VerificarFirmaCentral;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FamiliaController;
use App\Http\Controllers\Api\RetoController;
use App\Http\Controllers\Api\InscripcionController;
use App\Http\Controllers\Api\ContactoController;
use App\Http\Controllers\Api\ContrasenaController;
use App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Api\Web\WebController;

Route::prefix('auth')->group(function () {
    Route::post('login',    [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('logout',   [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('me',        [AuthController::class, 'me'])->middleware('auth:sanctum');
    // Panel de gestión web: crear la contraseña (invitación) y «la he olvidado»
    Route::post('olvide-contrasena', [ContrasenaController::class, 'olvide'])->middleware('throttle:5,1,panel-olvide')->name('auth.olvide');
    Route::post('crear-contrasena',  [ContrasenaController::class, 'crear'])->middleware('throttle:10,1,panel-crear-contrasena')->name('auth.crear-contrasena');
});

Route::get('familias',           [FamiliaController::class, 'index']);
Route::get('familias/{familia}', [FamiliaController::class, 'show']);
// Cada throttle de ruta lleva su propio prefijo (3.er parámetro): sin él comparte contador con el
// global de la API (120/min, bootstrap/app.php) y cada petición cuenta dos veces.
Route::get('retos',              [RetoController::class, 'index'])->middleware('throttle:30,1,retos-listado');
Route::get('retos/{reto}',       [RetoController::class, 'show']);

Route::post('contacto', [ContactoController::class, 'store'])->middleware('throttle:10,1,contacto');

// ── Contenido gestionado de la web pública (panel de gestión web) ──────────────
Route::prefix('web')->name('web.')->middleware('throttle:60,1,web-publica')->group(function () {
    Route::get('configuracion',     [WebController::class, 'configuracion'])->name('configuracion');
    Route::get('sitemap.xml',       [WebController::class, 'sitemap'])->name('sitemap');
    Route::get('paginas/{clave}',   [WebController::class, 'pagina'])->where('clave', '[a-z0-9\-_]+')->name('paginas.show');
    Route::get('categorias',        [WebController::class, 'categorias'])->name('categorias');
    Route::get('entradas',          [WebController::class, 'entradas'])->name('entradas.index');
    Route::get('entradas/{slug}',   [WebController::class, 'entrada'])->name('entradas.show')->where('slug', '[a-z0-9\-]+');
});

// ── DuaLab Central → «Primer contacto» (servidor a servidor, sin sesión: firma HMAC) ──
Route::prefix('integraciones/central')->name('central.')
    ->middleware([VerificarFirmaCentral::class, 'throttle:120,1,central-api'])->group(function () {
        Route::get('formularios/exportar',     [CentralFormularioController::class, 'exportar'])->middleware('throttle:10,1,central-exportar')->name('formularios.exportar');
        Route::get('formularios',              [CentralFormularioController::class, 'index'])->name('formularios.index');
        Route::get('formularios/{contacto}',   [CentralFormularioController::class, 'show'])->whereNumber('contacto')->name('formularios.show');
        Route::patch('formularios/{contacto}', [CentralFormularioController::class, 'update'])->whereNumber('contacto')->name('formularios.update');

        // Correo de info@ (específicas antes de correo/{correo}; el adjunto tiene que ser de ese correo)
        Route::post('correo/enviar',                    [CentralCorreoController::class, 'enviar'])->middleware('throttle:20,1,central-correo-enviar')->name('correo.enviar');
        Route::get('correo/envios/{envio}',             [CentralCorreoController::class, 'envio'])->whereNumber('envio')->name('correo.envio');
        Route::post('correo/envios/{envio}/reintentar', [CentralCorreoController::class, 'reintentar'])->whereNumber('envio')->middleware('throttle:10,1,central-correo-enviar')->name('correo.reintentar');
        Route::delete('correo/envios/{envio}',          [CentralCorreoController::class, 'descartar'])->whereNumber('envio')->name('correo.descartar');
        Route::delete('correo/papelera',                [CentralCorreoController::class, 'vaciarPapelera'])->name('correo.vaciar');
        Route::get('correo',                            [CentralCorreoController::class, 'index'])->name('correo.index');
        Route::get('correo/{correo}',                   [CentralCorreoController::class, 'show'])->whereNumber('correo')->name('correo.show');
        Route::patch('correo/{correo}',                 [CentralCorreoController::class, 'update'])->whereNumber('correo')->name('correo.update');
        Route::delete('correo/{correo}',                [CentralCorreoController::class, 'destroy'])->whereNumber('correo')->name('correo.destroy');
        Route::get('correo/{correo}/adjuntos/{adjunto}', [CentralCorreoController::class, 'adjunto'])
            ->scopeBindings()->whereNumber(['correo', 'adjunto'])->middleware('throttle:30,1,central-correo-adjuntos')->name('correo.adjunto');
    });

Route::middleware('auth:sanctum')->group(function () {
    Route::get('mis-inscripciones',              [InscripcionController::class, 'index']);
    Route::post('retos/{reto}/inscribirse',      [InscripcionController::class, 'store']);
    Route::delete('inscripciones/{inscripcion}', [InscripcionController::class, 'destroy']);

    Route::middleware('can:manage-retos')->group(function () {
        Route::post('retos',          [RetoController::class, 'store']);
        Route::put('retos/{reto}',    [RetoController::class, 'update']);
        Route::delete('retos/{reto}', [RetoController::class, 'destroy']);
    });

    // ── Panel de gestión web (/gestion-web): admin, editor y atención de contactos ──
    Route::prefix('admin')->name('admin.')->middleware('can:entrar-panel')->group(function () {
        // Perfil propio (como «Mi usuario» de la app DuaLab)
        Route::get('perfil',   [Admin\PerfilController::class, 'show'])->name('perfil.show');
        Route::patch('perfil', [Admin\PerfilController::class, 'update'])->middleware('throttle:10,1,panel-perfil')->name('perfil.update');

        // Formularios y correo de info@: se atienden en DuaLab Central («Primer contacto») por la API
        // firmada de arriba (integraciones/central); ya no hay rutas del panel para ellos

        // Equipo: usuarios del panel y registro de actividad (solo admin)
        Route::middleware('can:administrar-web')->group(function () {
            Route::get('actividad',                       [Admin\ActividadController::class, 'index'])->name('actividad.index');
            Route::get('usuarios',                        [Admin\UsuarioController::class, 'index'])->name('usuarios.index');
            Route::post('usuarios',                       [Admin\UsuarioController::class, 'store'])->middleware('throttle:20,1,panel-usuarios-alta')->name('usuarios.store');
            Route::post('usuarios/{id}/restaurar',        [Admin\UsuarioController::class, 'restaurar'])->whereNumber('id')->name('usuarios.restaurar');
            Route::delete('usuarios/{id}/definitivo',     [Admin\UsuarioController::class, 'destruir'])->whereNumber('id')->name('usuarios.destruir');
            Route::post('usuarios/{usuario}/bloquear',    [Admin\UsuarioController::class, 'bloquear'])->name('usuarios.bloquear');
            Route::post('usuarios/{usuario}/desbloquear', [Admin\UsuarioController::class, 'desbloquear'])->name('usuarios.desbloquear');
            Route::post('usuarios/{usuario}/activar',     [Admin\UsuarioController::class, 'activar'])->name('usuarios.activar');
            Route::post('usuarios/{usuario}/reenviar-correo', [Admin\UsuarioController::class, 'reenviarCorreo'])->middleware('throttle:6,1,panel-reenviar-correo')->name('usuarios.reenviar');
            Route::patch('usuarios/{usuario}',            [Admin\UsuarioController::class, 'update'])->name('usuarios.update');
            Route::delete('usuarios/{usuario}',           [Admin\UsuarioController::class, 'destroy'])->name('usuarios.destroy');

        });
    });

    // ── Contenido y ajustes del panel: admin y editor ─────────────────────────
    Route::prefix('admin')->name('admin.')->middleware('can:gestionar-web')->group(function () {
        Route::get('escritorio', Admin\EscritorioController::class)->name('escritorio');
        Route::get('vista-buscadores', Admin\VistaBuscadoresController::class)->middleware('throttle:30,1,panel-vista-buscadores')->name('vista-buscadores');
        Route::get('actividad/{tipo}/{ref}', [Admin\ActividadController::class, 'historial'])->name('actividad.historial');

        Route::get('paginas',          [Admin\PaginaController::class, 'index'])->name('paginas.index');
        Route::get('paginas/{clave}',  [Admin\PaginaController::class, 'show'])->name('paginas.show');
        Route::put('paginas/{clave}',  [Admin\PaginaController::class, 'update'])->name('paginas.update');

        // Específicas antes de entradas/{entrada}
        Route::post('entradas/{id}/restaurar',  [Admin\EntradaController::class, 'restaurar'])->whereNumber('id')->name('entradas.restaurar');
        Route::delete('entradas/{id}/definitivo', [Admin\EntradaController::class, 'eliminarDefinitivamente'])->whereNumber('id')->name('entradas.definitivo');
        Route::post('entradas/{entrada}/traducir', [Admin\EntradaController::class, 'traducir'])->name('entradas.traducir');
        // Aprobación (solo admin, EntradaPolicy::aprobar)
        Route::post('entradas/{entrada}/aprobar',  [Admin\EntradaController::class, 'aprobar'])->name('entradas.aprobar');
        Route::post('entradas/{entrada}/devolver', [Admin\EntradaController::class, 'devolver'])->name('entradas.devolver');
        Route::post('entradas/{entrada}/retirar',  [Admin\EntradaController::class, 'retirar'])->name('entradas.retirar');
        Route::apiResource('entradas', Admin\EntradaController::class);

        Route::apiResource('categorias', Admin\CategoriaController::class)->except('show');

        Route::get('medios/almacenamiento',  [Admin\MedioController::class, 'almacenamiento'])->name('medios.almacenamiento');
        Route::post('medios/almacenamiento/comprobar', [Admin\MedioController::class, 'comprobarAlmacenamiento'])->middleware('throttle:6,1,panel-almacenamiento')->name('medios.almacenamiento.comprobar');
        Route::get('medios',            [Admin\MedioController::class, 'index'])->name('medios.index');
        Route::post('medios',           [Admin\MedioController::class, 'store'])->middleware('throttle:60,1,panel-medios-subida')->name('medios.store');
        Route::patch('medios/{medio}',  [Admin\MedioController::class, 'update'])->name('medios.update');
        Route::delete('medios/{medio}', [Admin\MedioController::class, 'destroy'])->name('medios.destroy');

        // Ajustes: seo (editores); analitica e integraciones (solo admin, ver AjusteController)
        Route::post('ajustes/integraciones/probar-webhook', [Admin\AjusteController::class, 'probarWebhook'])->middleware('throttle:5,1,panel-probar-webhook')->name('ajustes.probar-webhook');
        Route::get('ajustes/{grupo}',                       [Admin\AjusteController::class, 'show'])->name('ajustes.show');
        Route::put('ajustes/{grupo}',                       [Admin\AjusteController::class, 'update'])->name('ajustes.update');
        Route::delete('ajustes/{grupo}/{nombre}',           [Admin\AjusteController::class, 'borrarSecreto'])->name('ajustes.borrar-secreto');

        // Idiomas: los editores los ven (para traducir); añadir/activar/quitar, solo admin
        Route::get('idiomas', [Admin\IdiomaController::class, 'index'])->name('idiomas.index');
        Route::middleware('can:administrar-web')->group(function () {
            Route::apiResource('idiomas', Admin\IdiomaController::class)->except(['index', 'show']);
        });
    });
});
