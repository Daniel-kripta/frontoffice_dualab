<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ListarEntradasPublicasRequest;
use App\Http\Resources\Web\EntradaPublicaResource;
use App\Models\Ajuste;
use App\Models\Categoria;
use App\Models\Entrada;
use App\Models\Idioma;
use App\Models\Pagina;
use App\Services\PaginaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/** Contenido gestionado desde el panel, tal como lo consume la web pública. */
class WebController extends Controller
{
    public function __construct(private PaginaService $paginas) {}

    /** Ajustes públicos (SEO global, analítica) e idiomas activos. */
    public function configuracion(): JsonResponse
    {
        $datos = Cache::remember('web.configuracion', now()->addHour(), fn () => [
            'idiomas'      => Idioma::activos()->get(['codigo', 'nombre', 'por_defecto']),
            'ajustes'      => collect(config('cms.ajustes'))
                ->filter(fn ($def) => $def['publico'])
                ->map(fn ($def, $clave) => Ajuste::leer($clave)),
        ]);

        return response()->json($datos);
    }

    public function pagina(Request $request, string $clave): JsonResponse
    {
        abort_unless($this->paginas->existe($clave), 404);

        return response()->json($this->paginas->publico($clave, $request->query('idioma')));
    }

    public function entradas(ListarEntradasPublicasRequest $request): AnonymousResourceCollection
    {
        $f      = $request->validated();
        $idioma = Idioma::resolver($f['idioma'] ?? null);

        $entradas = Entrada::visibles()->with('categoria')
            ->where('idioma', $idioma)
            ->when($f['categoria'] ?? null, fn ($q, $slug) => $q->whereHas('categoria', fn ($c) => $c->where('slug', $slug)))
            ->orderByDesc('destacada')->latest('publicada_en')
            ->paginate(min((int) ($f['per_page'] ?? 9), 24));

        return EntradaPublicaResource::collection($entradas);
    }

    public function entrada(Request $request, string $slug): EntradaPublicaResource
    {
        $entrada = Entrada::visibles()->with(['categoria', 'seo'])
            ->where('idioma', Idioma::resolver($request->query('idioma')))
            ->where('slug', $slug)
            ->firstOrFail();

        return new EntradaPublicaResource($entrada);
    }

    public function categorias(): JsonResponse
    {
        return response()->json(
            Categoria::whereHas('entradas', fn ($q) => $q->visibles())->orderBy('nombre')->take(100)->get(['nombre', 'slug', 'color'])
        );
    }

    /** sitemap.xml: páginas publicadas e indexables + entradas visibles. */
    public function sitemap(): Response
    {
        $xml = Cache::remember('web.sitemap', now()->addHour(), function () {
            $base     = config('cms.url_publica');
            $paginas  = Pagina::with('seo')->get()->keyBy('clave');
            $idiomaBase = Idioma::codigoPorDefecto();
            $urls     = [];

            foreach (config('cms_paginas') as $clave => $def) {
                $pagina = $paginas->get($clave);
                $seo    = $pagina?->seo->firstWhere('idioma', $idiomaBase);
                if (! $def['ruta'] || str_contains($def['ruta'], ':') || $pagina?->publicada === false || $seo?->noindex) {
                    continue;
                }
                $urls[] = ['loc' => $base.$def['ruta'], 'lastmod' => $pagina?->updated_at];
            }

            Entrada::visibles()->with('seo')->latest('publicada_en')->take(1000)->get()
                ->reject(fn ($e) => $e->seo?->noindex)
                ->each(function ($e) use (&$urls, $base, $idiomaBase) {
                    $urls[] = [
                        'loc'     => $base.'/noticias/'.$e->slug.($e->idioma !== $idiomaBase ? '?idioma='.$e->idioma : ''),
                        'lastmod' => $e->updated_at,
                    ];
                });

            $cuerpo = collect($urls)->map(fn ($u) => '<url><loc>'.e($u['loc']).'</loc>'
                .($u['lastmod'] ? '<lastmod>'.$u['lastmod']->toAtomString().'</lastmod>' : '').'</url>')->implode('');

            return '<?xml version="1.0" encoding="UTF-8"?>'
                .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.$cuerpo.'</urlset>';
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
