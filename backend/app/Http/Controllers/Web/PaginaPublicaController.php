<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\HtmlPublicoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Páginas de la web pública (dualab.es/, /centros, /noticias/…) con su texto ya escrito para
 * Google y las IA. El .htaccess de la raíz de dualab.es manda aquí esas direcciones.
 */
class PaginaPublicaController extends Controller
{
    public function __invoke(Request $request, HtmlPublicoService $html): Response|RedirectResponse
    {
        $pagina = $html->pagina($request->path(), $request->query('idioma'));
        if (isset($pagina['redirigir'])) {
            // 301 si la dirección cambió para siempre (noticia renombrada); 302 si no
            return redirect($pagina['redirigir'], empty($pagina['permanente']) ? 302 : 301);
        }

        return response($pagina['html'], $pagina['estado'], [
            'Content-Type'  => 'text/html; charset=UTF-8',
            // Siempre la versión de ahora: tras un deploy cambian los assets y tras editar, el texto
            'Cache-Control' => 'no-cache',
            // La vista previa de Apariencia del panel la muestra en un iframe del mismo dominio.
            // Sin script-src: la web carga fuentes, Cloudinary, analítica y las dos APIs.
            'X-Frame-Options'         => 'SAMEORIGIN',
            'Content-Security-Policy' => "frame-ancestors 'self'; base-uri 'self'; object-src 'none'",
        ]);
    }
}
