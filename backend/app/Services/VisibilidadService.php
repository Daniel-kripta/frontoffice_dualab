<?php

namespace App\Services;

use App\Models\Ajuste;
use App\Models\Entrada;
use App\Models\Idioma;
use App\Models\Pagina;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Lo que leen los buscadores y las IA además de las páginas: robots.txt, llms.txt y los datos
 * de la organización (JSON-LD). Todo sale de los ajustes de SEO del panel.
 */
class VisibilidadService
{
    public const CACHES = ['web.robots', 'web.llms'];

    public static function olvidarCache(): void
    {
        foreach (self::CACHES as $clave) {
            Cache::forget($clave);
        }
    }

    public function robots(): string
    {
        return Cache::remember('web.robots', now()->addHour(), function () {
            $base = config('cms.url_publica');

            // «Indexar la web» apagado en SEO → nadie la rastrea
            if (Ajuste::leer('seo.indexar', true) === false) {
                return "User-agent: *\nDisallow: /\n";
            }

            $lineas = ['User-agent: *', 'Disallow: /gestion-web', ''];
            $bloqueados = array_intersect(Ajuste::leer('seo.ia_bloqueados', []) ?? [], array_keys(config('cms.bots_ia')));
            foreach ($bloqueados as $bot) {
                array_push($lineas, "User-agent: $bot", 'Disallow: /', '');
            }
            $lineas[] = "Sitemap: $base/sitemap.xml";

            return implode("\n", $lineas)."\n";
        });
    }

    /** /llms.txt (llmstxt.org): resumen en Markdown de DuaLab y sus páginas para las IA. */
    public function llms(): string
    {
        return Cache::remember('web.llms', now()->addHour(), function () {
            $base   = config('cms.url_publica');
            $idioma = Idioma::codigoPorDefecto();
            $nombre = Ajuste::leer('seo.org_nombre') ?: Ajuste::leer('seo.titulo_sitio') ?: 'DuaLab';

            $partes = ["# $nombre"];
            if ($descripcion = Ajuste::leer('seo.descripcion')) {
                $partes[] = '> '.$this->unaLinea($descripcion);
            }
            if ($resumen = Ajuste::leer('seo.llms_resumen')) {
                $partes[] = trim($resumen);
            }

            $paginas = Pagina::with(['seo', 'contenidos' => fn ($q) => $q->where('idioma', $idioma)->where('campo', 'hero.descripcion')])
                ->get()->keyBy('clave');
            $lista = [];
            foreach (config('cms_paginas') as $clave => $def) {
                $pagina = $paginas->get($clave);
                $seo    = $pagina?->seo->firstWhere('idioma', $idioma);
                if (! $def['ruta'] || str_contains($def['ruta'], ':') || $pagina?->publicada === false || $seo?->noindex) {
                    continue;
                }
                $texto = $seo?->meta_descripcion
                    ?: $pagina?->contenidos->first()?->valor
                    ?: ($def['secciones']['hero']['campos']['descripcion']['defecto'] ?? null);
                $lista[] = "- [{$def['titulo']}]($base{$def['ruta']})".($texto ? ': '.$this->unaLinea($texto) : '');
            }
            if ($lista) {
                $partes[] = "## Páginas\n\n".implode("\n", $lista);
            }

            // Preguntas frecuentes de todas las páginas: respuestas listas para que una IA las cite
            $faq = collect(config('cms_paginas'))->keys()
                ->reject(fn ($clave) => $paginas->get($clave)?->publicada === false)
                ->flatMap(fn ($clave) => array_map(fn ($p) => "### {$this->unaLinea($p['pregunta'])}\n\n{$this->unaLinea($p['respuesta'], 600)}",
                    app(PaginaService::class)->preguntas($clave, $idioma)));
            if ($faq->isNotEmpty()) {
                $partes[] = "## Preguntas frecuentes\n\n".$faq->implode("\n\n");
            }

            $noticias = Entrada::visibles()->where('idioma', $idioma)->latest('publicada_en')->take(20)->get()
                ->map(fn ($e) => "- [{$this->unaLinea($e->titulo)}]($base/noticias/{$e->slug})".($e->extracto ? ': '.$this->unaLinea($e->extracto) : ''));
            if ($noticias->isNotEmpty()) {
                $partes[] = "## Noticias recientes\n\n".$noticias->implode("\n");
            }

            $contacto = array_filter([
                "- Formulario: $base/contacto",
                ($email = Ajuste::leer('seo.org_email')) ? "- Correo: $email" : null,
                ($tel = Ajuste::leer('seo.org_telefono')) ? "- Teléfono: $tel" : null,
            ]);
            $partes[] = "## Contacto\n\n".implode("\n", $contacto);

            return implode("\n\n", $partes)."\n";
        });
    }

    /** Organización en schema.org, para el JSON-LD de todas las páginas. Null si no hay nombre. */
    public function organizacion(): ?array
    {
        $base   = config('cms.url_publica');
        $nombre = Ajuste::leer('seo.org_nombre') ?: Ajuste::leer('seo.titulo_sitio');
        if (! $nombre) {
            return null;
        }

        $direccion = array_filter([
            'streetAddress'   => Ajuste::leer('seo.org_direccion'),
            'postalCode'      => Ajuste::leer('seo.org_codigo_postal'),
            'addressLocality' => Ajuste::leer('seo.org_localidad'),
            'addressRegion'   => Ajuste::leer('seo.org_provincia'),
        ]);

        return array_filter([
            '@context'    => 'https://schema.org',
            '@type'       => 'Organization',
            '@id'         => "$base/#organizacion",
            'name'        => $nombre,
            'url'         => "$base/",
            'logo'        => Ajuste::leer('seo.org_logo') ?: Ajuste::leer('apariencia.logo'),
            'description' => Ajuste::leer('seo.descripcion'),
            'email'       => Ajuste::leer('seo.org_email'),
            'telephone'   => Ajuste::leer('seo.org_telefono'),
            'address'     => $direccion ? ['@type' => 'PostalAddress', 'addressCountry' => 'ES'] + $direccion : null,
            'sameAs'      => array_values(Ajuste::leer('seo.org_redes', []) ?? []) ?: null,
        ]);
    }

    /** Texto en una línea y sin Markdown que lo rompa (los textos los escriben editores). */
    private function unaLinea(string $texto, int $max = 300): string
    {
        return Str::limit(trim(preg_replace('/\s+/', ' ', str_replace(['[', ']'], ['(', ')'], strip_tags($texto)))), $max);
    }
}
