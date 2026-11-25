<?php

namespace App\Services;

use App\Jobs\MarcarLeidoEnBuzonJob;
use App\Models\Contacto;
use App\Models\CorreoAdjunto;
use App\Models\CorreoEnvio;
use App\Models\CorreoMensaje;
use App\Models\CorreoSincronizacion;
use App\Support\ContactoResumen;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Bandeja de Correo del panel: lee la copia local (CorreoSincronizacionService la llena).
 * Leer o marcar como no leído se lleva al buzón (MarcarLeidoEnBuzonJob); archivar es solo del panel.
 */
class BandejaCorreoService
{
    public function listar(array $filtros, int $porPagina): LengthAwarePaginator
    {
        return $this->consulta($filtros)->orderByDesc('fecha')->orderByDesc('id')->paginate($porPagina);
    }

    /**
     * Contadores por estado, estado de la sincronización y el aviso de retención. Con $filtros, los
     * contadores respetan incluir_* / excluir_* (bandejas aparte de Central), no la búsqueda ni el estado.
     */
    public function resumen(string $buzon, array $filtros = []): array
    {
        $porEstado = $this->porOrigen(
            CorreoMensaje::where('buzon', $buzon)->where('carpeta', CorreoSincronizacionService::rutaCarpeta($buzon, 'recibidos')),
            $filtros,
        )->selectRaw('estado, count(*) as total')->groupBy('estado')->pluck('total', 'estado');
        $sync = CorreoSincronizacion::where('buzon', $buzon)->orderByDesc('sincronizado_en')->first();
        $config = config("correo.buzones.{$buzon}");

        return [
            'por_estado'   => collect(CorreoMensaje::ESTADOS)->mapWithKeys(fn ($e) => [$e => (int) ($porEstado[$e] ?? 0)]),
            'buzon'        => ['clave' => $buzon, 'email' => $config['email'], 'configurado' => (bool) $config['password']],
            'sincronizado' => [
                'en'        => $sync?->sincronizado_en,
                'error'     => $sync?->ultimo_error,
                // Correos que no se han podido copiar tras 3 intentos: solo están en el webmail
                'omitidos'  => (int) CorreoSincronizacion::where('buzon', $buzon)->sum('omitidos'),
            ],
            'retencion'    => [
                'meses'   => config('correo.retencion_meses'),
                'desde'   => CorreoMensaje::fechaLimite()->toDateString(),
                'webmail' => config('cms.buzon.url'),
            ],
        ];
    }

    public function marcarLeido(CorreoMensaje $correo): void
    {
        if ($correo->estado === 'nuevo') {
            $correo->forceFill(['estado' => 'leido', 'leido_en' => now()])->save();
            MarcarLeidoEnBuzonJob::siProcede($correo, true);
        }
    }

    /** Archivar es solo del panel; lo que cambia en el buzón es si está leído o no. */
    public function cambiarEstado(CorreoMensaje $correo, string $estado): void
    {
        $eraNuevo = $correo->estado === 'nuevo';
        $correo->forceFill([
            'estado'   => $estado,
            'leido_en' => $estado === 'nuevo' ? null : ($correo->leido_en ?? now()),
        ])->save();

        if ($eraNuevo !== ($estado === 'nuevo')) {
            MarcarLeidoEnBuzonJob::siProcede($correo, $estado !== 'nuevo');
        }
    }

    /** Borrar del todo: solo lo que ya está en la papelera, con los archivos de sus adjuntos. */
    public function vaciarPapelera(string $buzon): int
    {
        $n = 0;
        CorreoMensaje::where('buzon', $buzon)->where('estado', 'papelera')->with('adjuntos')
            ->each(function (CorreoMensaje $m) use (&$n) { $m->delete(); $n++; });

        return $n;
    }

    /** Siempre como descarga (nunca se abre en el navegador) y sin adivinar el tipo. */
    public function descargar(CorreoAdjunto $adjunto): StreamedResponse
    {
        abort_unless($adjunto->ruta && Storage::disk(CorreoAdjunto::DISCO)->exists($adjunto->ruta), 404);

        return Storage::disk(CorreoAdjunto::DISCO)->download($adjunto->ruta, $adjunto->nombre, [
            'Content-Type'           => 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Remitentes de la página que ya nos escribieron por un formulario de la web:
     * email (en minúsculas) => tipo (centro, empresa…). Una sola consulta por página.
     */
    public function remitentesConocidos(Collection $correos): array
    {
        $emails = $correos->pluck('de_email')->filter()->map(fn ($e) => strtolower($e))->unique()->values();
        if ($emails->isEmpty()) {
            return [];
        }

        return Contacto::whereIn(DB::raw('LOWER(email)'), $emails)->latest()->get(['email', 'tipo'])
            ->unique(fn ($c) => strtolower($c->email))
            ->mapWithKeys(fn ($c) => [strtolower($c->email) => [
                'tipo' => $c->tipo, 'tipo_nombre' => ContactoResumen::TIPOS[$c->tipo] ?? $c->tipo,
            ]])->all();
    }

    /** Envíos que aún no han salido o han fallado (aviso encima de la bandeja). */
    public function salida(string $buzon): array
    {
        return CorreoEnvio::where('buzon', $buzon)->whereIn('estado', ['pendiente', 'error'])
            ->latest()->take(10)->get(['id', 'asunto', 'para', 'estado', 'error', 'created_at'])->toArray();
    }

    private function consulta(array $f): Builder
    {
        $buzon = $f['buzon'] ?? 'info';

        return $this->porOrigen(CorreoMensaje::query(), $f)
            ->where('buzon', $buzon)
            ->where('carpeta', CorreoSincronizacionService::rutaCarpeta($buzon, $f['carpeta'] ?? 'recibidos'))
            ->when($f['estado'] ?? null, fn ($q, $e) => $q->where('estado', $e), fn ($q) => $q->whereNotIn('estado', ['archivado', 'papelera']))
            ->when($f['adjuntos'] ?? false, fn ($q) => $q->where('tiene_adjuntos', true))
            ->when($f['buscar'] ?? null, function ($q, $texto) {
                $like = '%'.addcslashes($texto, '%_\\').'%';
                $q->where(fn ($w) => $w->where('asunto', 'like', $like)->orWhere('de_email', 'like', $like)->orWhere('de_nombre', 'like', $like));
            });
    }

    /**
     * incluir_asunto / incluir_de: solo lo que lleva ese texto en el asunto O llega de esos remitentes.
     * excluir_asunto / excluir_de: todo menos eso. Los emails se comparan sin distinguir mayúsculas.
     */
    private function porOrigen(Builder $q, array $f): Builder
    {
        $like = fn (string $t) => '%'.addcslashes($t, '%_\\').'%';
        $emails = fn (array $l) => array_values(array_unique(array_map(fn ($e) => strtolower(trim((string) $e)), $l)));
        $incluirAsunto = $f['incluir_asunto'] ?? null;
        $incluirDe = $emails($f['incluir_de'] ?? []);

        if ($incluirAsunto || $incluirDe) {
            $q->where(function ($w) use ($incluirAsunto, $incluirDe, $like) {
                if ($incluirAsunto) {
                    $w->orWhere('asunto', 'like', $like($incluirAsunto));
                }
                if ($incluirDe) {
                    $w->orWhereIn(DB::raw('LOWER(de_email)'), $incluirDe);
                }
            });
        }
        if ($excluirAsunto = $f['excluir_asunto'] ?? null) {
            $q->where(fn ($w) => $w->whereNull('asunto')->orWhere('asunto', 'not like', $like($excluirAsunto)));
        }
        if ($excluirDe = $emails($f['excluir_de'] ?? [])) {
            $q->where(fn ($w) => $w->whereNull('de_email')->orWhereNotIn(DB::raw('LOWER(de_email)'), $excluirDe));
        }

        return $q;
    }
}
