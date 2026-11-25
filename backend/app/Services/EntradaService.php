<?php

namespace App\Services;

use App\Models\Entrada;
use App\Models\Redireccion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;

/**
 * Entradas del blog. El HTML de CKEditor se purifica aquí, antes de guardarlo
 * (perfil `cms` de config/purifier.php); la web lo vuelve a sanear con DOMPurify.
 */
class EntradaService
{
    public function __construct(private SeoService $seo) {}

    public function crear(array $datos, User $autor): Entrada
    {
        return DB::transaction(function () use ($datos, $autor) {
            $entrada = new Entrada($this->preparar($datos));
            $entrada->grupo_traduccion = (string) Str::uuid();
            $entrada->autor_id = $autor->id;
            $entrada->slug = $this->slugUnico($datos['slug'] ?? null, $datos['titulo'], $entrada->idioma);
            $this->registrarCambioDeEstado($entrada, $autor);
            $entrada->save();

            if (isset($datos['seo'])) {
                $this->seo->guardar($entrada, $entrada->idioma, $datos['seo']);
            }

            return $entrada;
        });
    }

    public function actualizar(Entrada $entrada, array $datos, User $usuario): Entrada
    {
        return DB::transaction(function () use ($entrada, $datos, $usuario) {
            $entrada->fill($this->preparar($datos, $entrada));

            // ¿Estaba en la web? Entonces su dirección antigua ya la conocen Google y las IA
            $eraVisible = $entrada->getOriginal('estado') === 'publicada' && $entrada->getOriginal('publicada_en')?->isPast();
            $slugAnterior = $entrada->getOriginal('slug');

            if (array_key_exists('slug', $datos) || $entrada->isDirty('titulo') && $entrada->estado === 'borrador') {
                $entrada->slug = $this->slugUnico($datos['slug'] ?? null, $entrada->titulo, $entrada->idioma, $entrada->id);
            }
            $this->registrarCambioDeEstado($entrada, $usuario);
            $entrada->save();

            if ($eraVisible && $slugAnterior && $slugAnterior !== $entrada->slug) {
                $this->redirigir($entrada->idioma, "/noticias/$slugAnterior", "/noticias/{$entrada->slug}");
            }

            if (isset($datos['seo'])) {
                $this->seo->guardar($entrada, $entrada->idioma, $datos['seo']);
            }

            return $entrada;
        });
    }

    /** La dirección antigua lleva a la nueva (301), sin cadenas ni bucles si se vuelve a cambiar. */
    private function redirigir(string $idioma, string $origen, string $destino): void
    {
        Redireccion::where('idioma', $idioma)->where('origen', $destino)->delete();
        Redireccion::where('idioma', $idioma)->where('destino', $origen)->update(['destino' => $destino]);
        Redireccion::updateOrCreate(['idioma' => $idioma, 'origen' => $origen], ['destino' => $destino]);
    }

    /** Aprobar: se publica (ahora o en su fecha programada). */
    public function aprobar(Entrada $entrada, User $admin): Entrada
    {
        $entrada->estado = 'publicada';
        $entrada->publicada_en ??= now();
        $this->registrarCambioDeEstado($entrada, $admin);
        $entrada->save();

        return $entrada;
    }

    /** Devolver al autor con una nota: vuelve a borrador. */
    public function devolver(Entrada $entrada, string $nota): Entrada
    {
        $entrada->forceFill(['estado' => 'borrador', 'nota_revision' => strip_tags($nota)])->save();

        return $entrada;
    }

    /** Retirar de la web una entrada publicada (sin borrarla): vuelve a borrador. */
    public function retirar(Entrada $entrada): Entrada
    {
        $entrada->forceFill(['estado' => 'borrador', 'aprobada_por' => null, 'aprobada_en' => null])->save();

        return $entrada;
    }

    /** Fechas y responsables de cada paso del flujo borrador → pendiente → publicada. */
    private function registrarCambioDeEstado(Entrada $entrada, User $usuario): void
    {
        if (! $entrada->isDirty('estado') && $entrada->exists) {
            return;
        }
        if ($entrada->estado === 'pendiente') {
            $entrada->forceFill(['enviada_revision_en' => now(), 'nota_revision' => null]);
        }
        if ($entrada->estado === 'publicada') {
            $entrada->forceFill(['aprobada_por' => $usuario->id, 'aprobada_en' => now(), 'nota_revision' => null]);
        }
        if ($entrada->estado === 'borrador') {
            $entrada->forceFill(['aprobada_por' => null, 'aprobada_en' => null]);
        }
    }

    /** Crea la versión en otro idioma como borrador (copia para traducir). */
    public function traducir(Entrada $original, string $idioma, User $autor): Entrada
    {
        $existente = Entrada::withTrashed()->where('grupo_traduccion', $original->grupo_traduccion)
            ->where('idioma', $idioma)->first();
        if ($existente) {
            return $existente;
        }

        $copia = $original->replicate(['slug', 'estado', 'publicada_en', 'autor_id', 'deleted_at',
            'enviada_revision_en', 'aprobada_por', 'aprobada_en', 'nota_revision']);
        $copia->idioma   = $idioma;
        $copia->estado   = 'borrador';
        $copia->autor_id = $autor->id;
        $copia->slug     = $this->slugUnico($original->slug, $original->titulo, $idioma);
        $copia->save();

        return $copia;
    }

    private function preparar(array $datos, ?Entrada $actual = null): array
    {
        $campos = collect($datos)->only([
            'idioma', 'titulo', 'extracto', 'contenido', 'imagen_destacada',
            'categoria_id', 'estado', 'publicada_en', 'destacada',
        ])->all();

        if (array_key_exists('contenido', $campos)) {
            $campos['contenido'] = $campos['contenido'] ? Purifier::clean($campos['contenido'], 'cms') : null;
        }
        if (array_key_exists('extracto', $campos)) {
            $campos['extracto'] = $campos['extracto'] ? strip_tags($campos['extracto']) : null;
        }

        // Publicar sin fecha = ahora. Una fecha futura la deja programada.
        $estado = $campos['estado'] ?? $actual?->estado;
        if ($estado === 'publicada' && empty($campos['publicada_en']) && ! $actual?->publicada_en) {
            $campos['publicada_en'] = now();
        }

        // El idioma de una entrada existente no cambia: para otro idioma se traduce.
        if ($actual) {
            unset($campos['idioma']);
        }

        return $campos;
    }

    private function slugUnico(?string $deseado, string $titulo, string $idioma, ?int $ignorarId = null): string
    {
        $base = Str::slug($deseado ?: $titulo) ?: 'entrada';
        $base = Str::limit($base, 200, '');
        $slug = $base;
        $n    = 2;

        while (Entrada::withTrashed()->where('idioma', $idioma)->where('slug', $slug)
            ->when($ignorarId, fn ($q) => $q->whereKeyNot($ignorarId))->exists()) {
            $slug = "$base-".$n++;
        }

        return $slug;
    }
}
