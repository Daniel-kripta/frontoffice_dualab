<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Api\Web\WebController;
use App\Http\Controllers\Controller;
use App\Services\VisibilidadService;
use Illuminate\Http\Response;

/**
 * robots.txt, llms.txt y sitemap.xml en la raíz del dominio (dualab.es/robots.txt…).
 * El .htaccess de la raíz de dualab.es manda aquí esas tres direcciones.
 */
class VisibilidadController extends Controller
{
    public function __construct(private VisibilidadService $visibilidad) {}

    public function robots(): Response
    {
        return response($this->visibilidad->robots(), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function llms(): Response
    {
        return response($this->visibilidad->llms(), 200, ['Content-Type' => 'text/markdown; charset=UTF-8']);
    }

    public function sitemap(WebController $web): Response
    {
        return $web->sitemap();
    }
}
