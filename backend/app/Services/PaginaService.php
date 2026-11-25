<?php

namespace App\Services;

use App\Models\Idioma;
use App\Models\Pagina;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Páginas editables de la web pública. La estructura sale de config/cms_paginas.php;
 * en BD solo se guardan los campos cambiados respecto al texto original, por idioma.
 */
class PaginaService
{
    public function __construct(private SeoService $seo) {}

    public function existe(string $clave): bool
    {
        return config("cms_paginas.$clave") !== null;
    }

    /** Esquema aplanado: "seccion.campo" => definición. */
    public function campos(string $clave): array
    {
        $campos = [];
        foreach (config("cms_paginas.$clave.secciones", []) as $seccion => $def) {
            foreach ($def['campos'] as $campo => $defCampo) {
                $campos["$seccion.$campo"] = $defCampo;
            }
        }

        return $campos;
    }

    /** Listado para el panel: todas las páginas del esquema con su estado. */
    public function listado(): array
    {
        $paginas = Pagina::withCount('contenidos')->with('seo:id,seoable_type,seoable_id,idioma,meta_titulo,meta_descripcion')
            ->get()->keyBy('clave');

        return collect(config('cms_paginas'))->map(function (array $def, string $clave) use ($paginas) {
            $pagina = $paginas->get($clave);

            return [
                'clave'           => $clave,
                'titulo'          => $def['titulo'],
                'ruta'            => $def['ruta'],
                'padre'           => $def['padre'],
                'num_campos'      => count($this->campos($clave)),
                'campos_editados' => $pagina?->contenidos_count ?? 0,
                'publicada'       => $pagina?->publicada ?? true,
                'idiomas_activos' => $pagina?->idiomas_activos ?? [],
                'seo_completo'    => (bool) $pagina?->seo->first(fn ($s) => $s->meta_titulo && $s->meta_descripcion),
                'editada_en'      => $pagina?->updated_at,
            ];
        })->values()->all();
    }

    /** Detalle para el editor del panel: esquema + valores guardados en el idioma pedido. */
    public function detalle(string $clave, string $idioma): array
    {
        $def    = config("cms_paginas.$clave");
        $pagina = Pagina::with('seo')->firstWhere('clave', $clave);

        return [
            'clave'           => $clave,
            'titulo'          => $def['titulo'],
            'ruta'            => $def['ruta'],
            'padre'           => $def['padre'],
            'publicada'       => $pagina?->publicada ?? true,
            'idiomas_activos' => $pagina?->idiomas_activos ?? [],
            'idioma'          => $idioma,
            'secciones'       => collect($def['secciones'])->map(fn ($s, $k) => [
                'clave'  => $k,
                'titulo' => $s['titulo'],
                'campos' => collect($s['campos'])->map(fn ($c, $ck) => [
                    'clave'    => "$k.$ck",
                    'tipo'     => $c['tipo'],
                    'etiqueta' => $c['etiqueta'],
                    'ayuda'    => $c['ayuda'] ?? null,
                    'max'      => $c['max'] ?? null,
                    'defecto'  => $c['defecto'] ?? null,
                ])->values(),
            ])->values(),
            'valores'         => ($pagina ? $this->valores($pagina, $idioma) : []) ?: (object) [],
            'seo'             => $this->seo->paraPanel($pagina?->seo->firstWhere('idioma', $idioma)),
            'editada_en'      => $pagina?->updated_at,
        ];
    }

    /** Lo que necesita la web pública: solo los valores cambiados (la vista tiene los originales). */
    public function publico(string $clave, ?string $idiomaPedido): array
    {
        $idiomaBase = Idioma::codigoPorDefecto();
        $idioma     = Idioma::resolver($idiomaPedido);

        // Solo arrays en caché (cache.serializable_classes = false): el {} vacío se pone al salir
        $datos = Cache::remember("web.pagina.$clave.$idioma", now()->addHour(), function () use ($clave, $idioma, $idiomaBase) {
            $pagina = Pagina::with('seo')->firstWhere('clave', $clave);

            // Idioma no activado para esta página → se sirve en el idioma base.
            if ($pagina && $idioma !== $idiomaBase && ! in_array($idioma, $pagina->idiomas_activos ?? [], true)) {
                $idioma = $idiomaBase;
            }

            return [
                'clave'     => $clave,
                'idioma'    => $idioma,
                'publicada' => $pagina?->publicada ?? true,
                'valores'   => $pagina ? $this->optimizarImagenes($this->valores($pagina, $idioma)) : [],
                'seo'       => $this->seo->paraPublico($pagina?->seo->firstWhere('idioma', $idioma)),
            ];
        });

        return ['valores' => $datos['valores'] ?: (object) []] + $datos;
    }

    /**
     * Foto de lo que muestra la página (texto editado o el original) para el historial:
     * valores por campo y su nombre en el panel («Cabecera · Título»). Ver ActividadService::cambios.
     *
     * @return array{valores: array<string, mixed>, etiquetas: array<string, string>}
     */
    public function foto(string $clave, string $idioma): array
    {
        $d = $this->detalle($clave, $idioma);
        $editados = (array) $d['valores'];
        $valores = ['publicada' => $d['publicada'], 'idiomas_activos' => $d['idiomas_activos']];
        $etiquetas = ['publicada' => 'Publicada', 'idiomas_activos' => 'Idiomas activos'];

        foreach ($d['secciones'] as $seccion) {
            foreach ($seccion['campos'] as $c) {
                $valor = $editados[$c['clave']] ?? $c['defecto'];
                // Una galería se resume en cuántas fotos tiene: sus direcciones no dicen nada
                $valores[$c['clave']]   = $c['tipo'] === 'galeria' && is_array($valor) ? count($valor).' fotos' : $valor;
                $etiquetas[$c['clave']] = "{$seccion['titulo']} · {$c['etiqueta']}";
            }
        }
        foreach (SeoService::ETIQUETAS as $campo => $etiqueta) {
            $valores["seo.$campo"]   = $d['seo'][$campo] ?? null;
            $etiquetas["seo.$campo"] = $etiqueta;
        }

        return ['valores' => $valores, 'etiquetas' => $etiquetas];
    }

    /**
     * Preguntas frecuentes de una página (sección «preguntas»: item_N_titulo = pregunta,
     * item_N_texto = respuesta), con lo editado en el panel o el texto original.
     * Para los datos estructurados FAQPage y llms.txt.
     *
     * @return list<array{pregunta: string, respuesta: string}>
     */
    public function preguntas(string $clave, string $idioma): array
    {
        $campos = config("cms_paginas.$clave.secciones.preguntas.campos");
        if (! $campos) {
            return [];
        }
        $valores = (array) $this->publico($clave, $idioma)['valores'];
        $valor   = fn ($campo) => ($valores["preguntas.$campo"] ?? null) ?: ($campos[$campo]['defecto'] ?? null);

        $lista = [];
        for ($n = 1; isset($campos["item_{$n}_titulo"]); $n++) {
            $pregunta  = $valor("item_{$n}_titulo");
            $respuesta = $valor("item_{$n}_texto");
            if ($pregunta && $respuesta) {
                $lista[] = ['pregunta' => $pregunta, 'respuesta' => $respuesta];
            }
        }

        return $lista;
    }

    /**
     * Guarda los cambios del editor. Un campo vacío o igual al original se borra
     * (vuelve a mostrarse el texto original de la vista).
     */
    public function guardar(string $clave, array $datos, User $editor): void
    {
        $idioma = $datos['idioma'];
        $campos = $this->campos($clave);

        DB::transaction(function () use ($clave, $datos, $editor, $idioma, $campos) {
            $pagina = Pagina::firstOrCreate(['clave' => $clave]);

            if (array_key_exists('publicada', $datos)) {
                $pagina->publicada = $datos['publicada'];
            }
            if (array_key_exists('idiomas_activos', $datos)) {
                $pagina->idiomas_activos = array_values($datos['idiomas_activos'] ?? []);
            }
            $pagina->editada_por = $editor->id;
            $pagina->updated_at  = now(); // también cuando solo cambian textos
            $pagina->save();

            // valores llega anidado ({ hero: { titulo: … } }); en BD la clave es "hero.titulo".
            $valores = collect($datos['valores'] ?? [])
                ->flatMap(fn ($seccion, $s) => collect($seccion)->mapWithKeys(fn ($v, $c) => ["$s.$c" => $v]));

            foreach ($valores as $campo => $valor) {
                $def    = $campos[$campo];
                $valor  = $this->normalizar($def['tipo'], $valor);
                $esOriginal = $valor === null || $valor === $this->normalizar($def['tipo'], $def['defecto'] ?? null);

                if ($esOriginal) {
                    $pagina->contenidos()->where(['idioma' => $idioma, 'campo' => $campo])->delete();
                    continue;
                }

                $pagina->contenidos()->updateOrCreate(
                    ['idioma' => $idioma, 'campo' => $campo],
                    ['valor' => is_array($valor) ? json_encode($valor) : $valor],
                );
            }

            if (isset($datos['seo'])) {
                $this->seo->guardar($pagina, $idioma, $datos['seo']);
            }
        });

        $this->olvidarCache($clave);
    }

    public function olvidarCache(string $clave): void
    {
        Cache::forget('web.sitemap');
        Cache::forget('web.llms');
        foreach (Idioma::pluck('codigo') as $codigo) {
            Cache::forget("web.pagina.$clave.$codigo");
        }
    }

    /** Fotos de Cloudinary en formato y tamaño para la web (las guardadas no cambian). */
    private function optimizarImagenes(array $valores): array
    {
        return array_map(fn ($v) => match (true) {
            is_string($v) => CloudinaryService::optimizar($v),
            is_array($v) && array_is_list($v) => array_map(fn ($u) => is_string($u) ? CloudinaryService::optimizar($u, 1600) : $u, $v),
            default => $v,
        }, $valores);
    }

    private function valores(Pagina $pagina, string $idioma): array
    {
        $tipos = collect($this->campos($pagina->clave))->map(fn ($c) => $c['tipo']);

        return $pagina->contenidos()->where('idioma', $idioma)->pluck('valor', 'campo')
            ->filter(fn ($v, $campo) => $tipos->has($campo)) // campos retirados del esquema
            ->map(fn ($v, $campo) => in_array($tipos[$campo], ['boton', 'galeria'], true) ? json_decode($v, true) : $v)
            ->all();
    }

    private function normalizar(string $tipo, mixed $valor): string|array|null
    {
        if ($tipo === 'boton') {
            $texto = trim((string) ($valor['texto'] ?? ''));
            $url   = trim((string) ($valor['url'] ?? ''));

            return $texto === '' && $url === '' ? null : ['texto' => $texto, 'url' => $url];
        }

        if ($tipo === 'galeria') {
            $fotos = array_values(array_filter(array_map(fn ($u) => trim((string) $u), (array) $valor)));

            return $fotos ?: null;
        }

        $valor = trim((string) $valor);

        return $valor === '' ? null : $valor;
    }
}
