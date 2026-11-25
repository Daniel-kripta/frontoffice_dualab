<?php

namespace App\Services;

use App\Models\Ajuste;
use App\Models\Entrada;
use App\Models\Idioma;
use App\Models\Pagina;
use App\Models\Redireccion;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * La web pública tal como la leen Google y las IA: el index.html de la SPA con el <head> de
 * cada página (título, descripción, Open Graph, canonical, JSON-LD) y su texto dentro de #app.
 * Vue vacía #app al arrancar y pinta la página de siempre, así que la persona ve lo mismo.
 * El texto sale del panel (o de los textos por defecto de config/cms_paginas.php): es el mismo
 * que muestra la vista, siempre al día.
 */
class HtmlPublicoService
{
    // Rótulos decorativos: no son texto de lectura (los textos de botón sin enlace se saltan aparte)
    private const CAMPOS_DECORATIVOS = ['rotulo', 'etiqueta'];

    public function __construct(
        private PaginaService $paginas,
        private SeoService $seo,
        private VisibilidadService $visibilidad,
    ) {}

    /** @return array{html: string, estado: int}|array{redirigir: string} */
    public function pagina(string $ruta, ?string $idiomaPedido = null): array
    {
        $ruta   = '/'.trim($ruta, '/');
        $idioma = Idioma::resolver($idiomaPedido);

        if (preg_match('#^/noticias/([a-z0-9\-]+)$#', $ruta, $m)) {
            return $this->entrada($m[1], $idioma);
        }

        $clave = collect(config('cms_paginas'))->search(fn ($def) => ($def['ruta'] ?? null) === $ruta);
        if ($clave === false) {
            // Páginas de la web sin texto en el panel (ficha de familia o de reto): cabecera general
            return $this->montar($idioma, $this->cabecera(['titulo' => null, 'ruta' => $ruta]), ['navegacion' => $this->navegacion()]);
        }

        $datos = $this->paginas->publico($clave, $idioma);
        if ($datos['publicada'] === false) {
            return ['redirigir' => '/'];
        }
        $def     = config("cms_paginas.$clave");
        $valores = (array) $datos['valores'];

        $bloques = $this->bloques($def, $valores, primero: true);
        if ($clave !== 'contacto') {
            $bloques = [...$bloques, ...$this->bloques(config('cms_paginas.comun'), (array) $this->paginas->publico('comun', $idioma)['valores'], primero: false)];
        }

        // Preguntas frecuentes en datos estructurados: lo que más citan las IA al responder
        $preguntas = $this->paginas->preguntas($clave, $idioma);
        $faq = $preguntas ? [[
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => array_map(fn ($p) => [
                '@type' => 'Question', 'name' => $p['pregunta'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $p['respuesta']],
            ], $preguntas),
        ]] : [];

        return $this->montar($idioma, $this->cabecera([
            'titulo'  => $def['titulo_seo'] ?? $def['titulo'],
            'seo'     => $datos['seo'],
            'ruta'    => $ruta,
            'inicio'  => $clave === 'inicio',
            'json_ld' => $faq,
        ]), [
            'navegacion' => $this->navegacion(),
            'bloques'    => $bloques,
            'titulo'     => $def['titulo'],
            'noticias'   => $clave === 'noticias'
                ? Entrada::visibles()->with('categoria')->where('idioma', $idioma)->latest('publicada_en')->take(24)->get()
                : collect(),
            'contacto'   => $clave === 'contacto' ? $this->contacto() : null,
        ]);
    }

    /**
     * Lo que ve un rastreador en una página: título, descripción, datos estructurados,
     * encabezados y texto. Para «Cómo nos ven Google y la IA» del panel.
     */
    public function analizar(string $ruta, ?string $idioma = null): array
    {
        $pagina = $this->pagina($ruta, $idioma);
        if (isset($pagina['redirigir'])) {
            return ['estado' => 302, 'redirige_a' => $pagina['redirigir']];
        }

        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$pagina['html'], LIBXML_NONET);
        libxml_clear_errors();
        $xp = new \DOMXPath($dom);

        $datos = [];
        foreach ($xp->query('//script[@type="application/ld+json"]') as $script) {
            $json = json_decode($script->textContent, true);
            if (isset($json['@type'])) {
                $datos[] = $json['@type'];
            }
        }

        $app = $xp->query('//div[@id="app"]')->item(0);
        $encabezados = [];
        foreach ($xp->query('.//h1|.//h2|.//h3', $app) as $h) {
            $encabezados[] = ['nivel' => (int) substr($h->nodeName, 1), 'texto' => trim(preg_replace('/\s+/', ' ', $h->textContent))];
        }
        // Sin el menú: es el mismo en todas las páginas
        foreach ($xp->query('.//nav', $app) as $nav) {
            $nav->parentNode->removeChild($nav);
        }
        $texto = trim(preg_replace('/\s+/', ' ', $app?->textContent ?? ''));

        return [
            'estado'       => $pagina['estado'],
            'titulo'       => trim($xp->query('//title')->item(0)?->textContent ?? ''),
            'descripcion'  => $xp->query('//meta[@name="description"]')->item(0)?->getAttribute('content'),
            'canonical'    => $xp->query('//link[@rel="canonical"]')->item(0)?->getAttribute('href'),
            'imagen'       => $xp->query('//meta[@property="og:image"]')->item(0)?->getAttribute('content'),
            'noindex'      => str_contains((string) $xp->query('//meta[@name="robots"]')->item(0)?->getAttribute('content'), 'noindex'),
            'datos'        => $datos,
            'encabezados'  => $encabezados,
            'palabras'     => $texto === '' ? 0 : count(preg_split('/\s+/u', $texto)),
            'texto'        => $texto,
        ];
    }

    private function entrada(string $slug, string $idioma): array
    {
        $entrada = Entrada::visibles()->with(['categoria', 'seo'])->where('idioma', $idioma)->where('slug', $slug)->first();
        if (! $entrada && $destino = Redireccion::where('idioma', $idioma)->where('origen', "/noticias/$slug")->value('destino')) {
            $consulta = $idioma !== Idioma::codigoPorDefecto() ? "?idioma=$idioma" : '';

            return ['redirigir' => $destino.$consulta, 'permanente' => true];
        }
        if (! $entrada) {
            return $this->montar($idioma, $this->cabecera(['titulo' => 'Noticia no encontrada', 'ruta' => "/noticias/$slug", 'seo' => ['noindex' => true]]),
                ['navegacion' => $this->navegacion()], 404);
        }

        $seo    = $this->seo->paraPublico($entrada->seo);
        // La de redes de la entrada; si no tiene, su foto destacada; si tampoco, la general
        $imagen = $entrada->seo?->imagen_og
            ?: ($entrada->imagen_destacada ? CloudinaryService::optimizar($entrada->imagen_destacada, 1600) : $seo['imagen_og']);
        $base   = config('cms.url_publica');

        $articulo = array_filter([
            '@context'      => 'https://schema.org',
            '@type'         => 'NewsArticle',
            'headline'      => $entrada->titulo,
            'description'   => $entrada->extracto,
            'image'         => $imagen,
            'datePublished' => $entrada->publicada_en?->toAtomString(),
            'dateModified'  => $entrada->updated_at?->toAtomString(),
            'inLanguage'    => $entrada->idioma,
            'mainEntityOfPage' => "$base/noticias/{$entrada->slug}",
            'publisher'     => $this->visibilidad->organizacion() ? ['@id' => "$base/#organizacion"] : null,
        ]);

        return $this->montar($idioma, $this->cabecera([
            'titulo'      => $entrada->titulo,
            'seo'         => ['meta_descripcion' => $entrada->seo?->meta_descripcion ?: $entrada->extracto ?: $seo['meta_descripcion'], 'imagen_og' => $imagen] + $seo,
            'ruta'        => "/noticias/{$entrada->slug}",
            'tipo'        => 'article',
            'json_ld'     => [$articulo],
        ]), [
            'navegacion' => $this->navegacion(),
            'entrada'    => $entrada,
            'imagen'     => $imagen,
        ]);
    }

    /**
     * Secciones de una página del panel → bloques de texto (titular, párrafos, cifras, tarjetas, enlaces).
     * Las tarjetas son los campos item_N_titulo / item_N_texto (ventajas, pasos…).
     */
    private function bloques(array $def, array $valores, bool $primero): array
    {
        $bloques = [];
        foreach ($def['secciones'] ?? [] as $seccion => $sec) {
            $v = function (string $campo) use ($sec, $seccion, $valores) {
                $valor = $valores["$seccion.$campo"] ?? null;

                return ($valor === null || $valor === '' || $valor === []) ? ($sec['campos'][$campo]['defecto'] ?? null) : $valor;
            };

            $bloque = ['nivel' => 2, 'antetitulo' => null, 'titulo' => null, 'textos' => [], 'cifras' => [], 'items' => [], 'enlaces' => []];
            foreach ($sec['campos'] as $campo => $c) {
                $valor = $v($campo);
                // destacado y titulo_final van con el titulo; cifra_texto, con su cifra
                if ($valor === null || $valor === '' || in_array($campo, [...self::CAMPOS_DECORATIVOS, 'destacado', 'titulo_final', 'cifra_texto'], true)) {
                    continue;
                }
                if ($campo === 'antetitulo') {
                    $bloque['antetitulo'] = $valor;
                } elseif ($campo === 'titulo') {
                    // «Empieza con» + «DuaLab» + «en 4 pasos»: la parte en color puede ir en medio
                    $bloque['titulo'] = trim(implode(' ', array_filter([$valor, $v('destacado'), $v('titulo_final')])));
                } elseif (preg_match('/^item_(\d+)_punto_\d+$/', $campo, $m)) {
                    $bloque['items'][(int) $m[1]]['puntos'][] = $valor;
                } elseif (preg_match('/^item_(\d+)_(titulo|texto|cifra)$/', $campo, $m)) {
                    $bloque['items'][(int) $m[1]][$m[2]] = $valor;
                } elseif (in_array($campo, ['cifra', 'cifra_valor'], true)) {
                    // Tarjeta de cifra sobre una foto («+70%» + su texto)
                    $bloque['cifras'][] = trim($valor.' '.($v('cifra_texto') ?? ''));
                } elseif (str_starts_with($campo, 'cifra_')) {
                    $bloque['cifras'][] = trim($valor.' '.($v('etiqueta_'.substr($campo, 6)) ?? ''));
                } elseif ($c['tipo'] === 'boton') {
                    if ($enlace = $this->enlace($valor)) {
                        $bloque['enlaces'][] = $enlace;
                    }
                } elseif (in_array($c['tipo'], ['texto', 'texto_largo'], true) && ! preg_match('/^(boton|etiqueta_)/', $campo)) {
                    $bloque['textos'][] = $valor;
                }
            }
            if ($bloque['titulo'] && $primero) {
                $bloque['nivel'] = 1;
                $primero = false;
            }
            $bloque['items'] = array_values($bloque['items']);
            if ($bloque['titulo'] || $bloque['textos'] || $bloque['items']) {
                $bloques[] = $bloque;
            }
        }

        return $bloques;
    }

    /** Botón del panel → enlace de la web (las anclas #… solo tienen sentido con la página pintada). */
    private function enlace(mixed $boton): ?array
    {
        $texto = trim((string) ($boton['texto'] ?? ''));
        $url   = trim((string) ($boton['url'] ?? ''));

        return $texto !== '' && $url !== '' && ! str_starts_with($url, '#') ? ['texto' => $texto, 'url' => $url] : null;
    }

    /** Enlaces a las páginas publicadas: los buscadores llegan a todas desde cualquiera. */
    private function navegacion(): array
    {
        $ocultas = Pagina::where('publicada', false)->pluck('clave')->all();

        return collect(config('cms_paginas'))
            ->filter(fn ($def, $clave) => ($def['ruta'] ?? null) && ! str_contains($def['ruta'], ':') && ! in_array($clave, $ocultas, true))
            ->map(fn ($def) => ['texto' => $def['titulo'], 'url' => $def['ruta']])
            ->values()->all();
    }

    private function contacto(): array
    {
        return array_filter([
            'email'     => Ajuste::leer('seo.org_email'),
            'telefono'  => Ajuste::leer('seo.org_telefono'),
            'direccion' => implode(', ', array_filter([
                Ajuste::leer('seo.org_direccion'), Ajuste::leer('seo.org_codigo_postal'),
                Ajuste::leer('seo.org_localidad'), Ajuste::leer('seo.org_provincia'),
            ])),
        ]);
    }

    /** <title>, meta y JSON-LD: lo mismo que pone useSeo.js en el navegador. */
    private function cabecera(array $p): string
    {
        $base   = config('cms.url_publica');
        $seo    = ($p['seo'] ?? []) + $this->seo->paraPublico(null);
        $sitio  = Ajuste::leer('seo.titulo_sitio') ?: 'DuaLab';
        $sep    = Ajuste::leer('seo.separador') ?: '|';
        $titulo = ($seo['meta_titulo'] ?? null) ?: ($p['titulo'] ? "{$p['titulo']} $sep $sitio" : $sitio);
        $url    = $base.($p['ruta'] === '/' ? '/' : $p['ruta']);
        $imagen = $seo['imagen_og'] ?? null;

        $meta = [
            ['name', 'description', $seo['meta_descripcion'] ?? null],
            ['name', 'robots', ! empty($seo['noindex']) ? 'noindex, nofollow' : null],
            ['property', 'og:title', $titulo],
            ['property', 'og:description', $seo['meta_descripcion'] ?? null],
            ['property', 'og:image', $imagen],
            ['property', 'og:url', $url],
            ['property', 'og:type', $p['tipo'] ?? 'website'],
            ['property', 'og:site_name', $sitio],
            ['name', 'twitter:card', $imagen ? 'summary_large_image' : 'summary'],
            ['name', 'google-site-verification', Ajuste::leer('seo.search_console')],
        ];

        $html = '<title>'.e($titulo).'</title>';
        foreach ($meta as [$atributo, $clave, $valor]) {
            if ($valor !== null && $valor !== '') {
                $html .= "\n    <meta $atributo=\"".e($clave).'" content="'.e($valor).'" />';
            }
        }
        $html .= "\n    <link rel=\"canonical\" href=\"".e(($seo['canonical'] ?? null) ?: $url).'" />';

        $ld = $p['json_ld'] ?? [];
        if (! empty($p['inicio'])) {
            $ld[] = ['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => $sitio, 'url' => "$base/", 'inLanguage' => 'es'];
        }
        if ($organizacion = $this->visibilidad->organizacion()) {
            // Mismo id que pone useConfiguracionWeb.js: así no lo duplica
            $html .= "\n    ".$this->jsonLd($organizacion, 'ld-organizacion');
        }
        foreach ($ld as $datos) {
            $html .= "\n    ".$this->jsonLd($datos);
        }

        return $html;
    }

    /** JSON_HEX_TAG: un «</script>» en un texto no puede cerrar la etiqueta. */
    private function jsonLd(array $datos, ?string $id = null): string
    {
        return '<script type="application/ld+json"'.($id ? ' id="'.$id.'"' : '').'>'
            .json_encode($datos, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            .'</script>';
    }

    private function montar(string $idioma, string $cabecera, array $vista, int $estado = 200): array
    {
        $plantilla = $this->plantilla();
        $cuerpo    = view('web.pagina', $vista + ['bloques' => [], 'noticias' => collect(), 'entrada' => null, 'contacto' => null, 'titulo' => null])->render();

        $html = preg_replace('#<title>.*?</title>#s', str_replace(['\\', '$'], ['\\\\', '\\$'], $cabecera), $plantilla, 1, $conTitulo);
        if (! $conTitulo) {
            $html = str_replace('</head>', $cabecera."\n  </head>", $html);
        }
        $html = preg_replace('#<html lang="[^"]*"#', '<html lang="'.e($idioma).'"', $html, 1);
        $html = str_replace('<div id="app"></div>', '<div id="app">'.$cuerpo.'</div>', $html);

        return ['html' => $html, 'estado' => $estado];
    }

    /** index.html de la última build de la web (cambia de nombre de assets en cada deploy). */
    private function plantilla(): string
    {
        $ruta = config('cms.web_index');
        if (! $ruta || ! File::isReadable($ruta)) {
            Log::critical('No se encuentra el index.html de la web (WEB_INDEX_HTML)');
            throw new RuntimeException('Falta el index.html de la web.');
        }

        return File::get($ruta);
    }
}
