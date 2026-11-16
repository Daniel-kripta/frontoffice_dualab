<?php

namespace App\Services;

use App\Models\Contacto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Bandeja de formularios recibidos (equivalente a Flamingo). Cada envío se
 * clasifica por formulario (agente + acción) y por la página desde la que llegó.
 */
class BandejaFormulariosService
{
    public const FORMULARIOS = [
        'rapido'         => 'Contacto rápido (inicio)',
        'centro'         => 'Centros educativos',
        'empresa'        => 'Empresas',
        'alumno'         => 'Alumnado',
        'administracion' => 'Administraciones',
    ];

    public const ACCIONES = [
        'registro' => 'Quiere registrarse',
        'info'     => 'Pide información',
        'demo'     => 'Pide una demo',
    ];

    public static function formularioDe(Contacto $c): string
    {
        return $c->accion ? $c->tipo : 'rapido';
    }

    public function listar(array $filtros, int $porPagina): LengthAwarePaginator
    {
        return $this->consulta($filtros)->latest()->paginate($porPagina);
    }

    /** Contadores para las pestañas y el menú lateral. */
    public function resumen(): array
    {
        $porEstado = Contacto::selectRaw('estado, count(*) as total')->groupBy('estado')->pluck('total', 'estado');

        $porFormulario = Contacto::where('estado', '!=', 'archivado')
            ->selectRaw("CASE WHEN accion IS NULL THEN 'rapido' ELSE tipo END as formulario, count(*) as total")
            ->groupBy('formulario')->pluck('total', 'formulario');

        return [
            'por_estado'     => collect(Contacto::ESTADOS)->mapWithKeys(fn ($e) => [$e => (int) ($porEstado[$e] ?? 0)]),
            'por_formulario' => collect(self::FORMULARIOS)->map(fn ($nombre, $clave) => [
                'clave' => $clave, 'nombre' => $nombre, 'total' => (int) ($porFormulario[$clave] ?? 0),
            ])->values(),
            'paginas_origen' => Contacto::whereNotNull('pagina_origen')->distinct()->orderBy('pagina_origen')
                ->limit(50)->pluck('pagina_origen'),
        ];
    }

    public function marcarLeido(Contacto $contacto): void
    {
        if ($contacto->estado === 'nuevo') {
            $contacto->forceFill(['estado' => 'leido', 'leido_en' => now()])->save();
        }
    }

    public function cambiarEstado(Contacto $contacto, string $estado): void
    {
        $contacto->forceFill([
            'estado'   => $estado,
            'leido_en' => $estado === 'nuevo' ? null : ($contacto->leido_en ?? now()),
        ])->save();
    }

    /** CSV para importar en un CRM. Neutraliza fórmulas (inyección CSV en Excel). */
    public function exportar(array $filtros): StreamedResponse
    {
        $consulta = $this->consulta($filtros)->latest();

        return response()->streamDownload(function () use ($consulta) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM: Excel abre bien las tildes
            fputcsv($out, ['Fecha', 'Formulario', 'Acción', 'Nombre', 'Apellidos', 'Email', 'Teléfono', 'Cargo', 'Página', 'Estado', 'Datos'], ';');

            $consulta->chunk(500, function ($contactos) use ($out) {
                foreach ($contactos as $c) {
                    $fila = [
                        $c->created_at?->format('Y-m-d H:i'),
                        self::FORMULARIOS[self::formularioDe($c)] ?? $c->tipo,
                        self::ACCIONES[$c->accion] ?? '',
                        $c->nombre, $c->apellidos, $c->email, $c->telefono, $c->cargo,
                        $c->pagina_origen, $c->estado,
                        $c->datos ? json_encode($c->datos, JSON_UNESCAPED_UNICODE) : '',
                    ];
                    fputcsv($out, array_map(fn ($v) => $this->celdaSegura($v), $fila), ';');
                }
            });
            fclose($out);
        }, 'formularios-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function consulta(array $f): Builder
    {
        return Contacto::query()
            ->when($f['estado'] ?? null, fn ($q, $e) => $q->where('estado', $e),
                fn ($q) => $q->where('estado', '!=', 'archivado'))
            ->when($f['formulario'] ?? null, fn ($q, $form) => $form === 'rapido'
                ? $q->whereNull('accion')
                : $q->whereNotNull('accion')->where('tipo', $form))
            ->when($f['accion'] ?? null, fn ($q, $a) => $q->where('accion', $a))
            ->when($f['pagina_origen'] ?? null, fn ($q, $p) => $q->where('pagina_origen', $p))
            ->when($f['desde'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($f['hasta'] ?? null, fn ($q, $h) => $q->whereDate('created_at', '<=', $h))
            ->when($f['buscar'] ?? null, function ($q, $texto) {
                $like = '%'.addcslashes($texto, '%_\\').'%';
                $q->where(fn ($w) => $w->where('nombre', 'like', $like)
                    ->orWhere('apellidos', 'like', $like)
                    ->orWhere('email', 'like', $like));
            });
    }

    private function celdaSegura(mixed $valor): string
    {
        $valor = (string) $valor;

        return preg_match('/^[=+\-@\t\r]/', $valor) ? "'".$valor : $valor;
    }
}
