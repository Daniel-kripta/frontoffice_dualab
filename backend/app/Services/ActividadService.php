<?php

namespace App\Services;

use App\Models\ActividadPanel;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Registro de actividad del panel: quién aporta qué. La descripción va sin sujeto
 * («editó la página «Centros»»): el panel antepone el nombre de quien lo hizo.
 * Nunca datos personales de terceros: los formularios se citan por su número.
 */
class ActividadService
{
    /** @param list<array{campo: string, antes: ?string, despues: ?string}>|null $detalle qué cambió (ver cambios()) */
    public function registrar(User $usuario, string $seccion, string $accion, string $descripcion, ?string $objetoTipo = null, int|string|null $objetoRef = null, ?array $detalle = null): void
    {
        ActividadPanel::create([
            'user_id'        => $usuario->id,
            'usuario_nombre' => Str::limit($usuario->name, 120, ''),
            'seccion'        => $seccion,
            'accion'         => $accion,
            'descripcion'    => Str::limit($descripcion, 250),
            'objeto_tipo'    => $objetoTipo,
            'objeto_ref'     => $objetoRef === null ? null : (string) $objetoRef,
            'detalle'        => $detalle ? array_slice($detalle, 0, self::MAX_CAMBIOS) : null,
        ]);
    }

    private const MAX_CAMBIOS = 60;

    /**
     * Qué cambió entre dos fotos de un objeto: solo las claves de $etiquetas («titulo» => «Título»)
     * cuyo valor es distinto. Los valores van en texto plano y recortados; los de $resumidas
     * (p. ej. el contenido de una noticia, una contraseña) solo dicen que cambiaron.
     *
     * @return list<array{campo: string, antes: ?string, despues: ?string}>
     */
    public static function cambios(array $antes, array $despues, array $etiquetas, array $resumidas = []): array
    {
        $cambios = [];
        foreach ($etiquetas as $clave => $etiqueta) {
            $a = self::texto($antes[$clave] ?? null);
            $d = self::texto($despues[$clave] ?? null);
            if ($a === $d) {
                continue;
            }
            $cambios[] = in_array($clave, $resumidas, true)
                ? ['campo' => $etiqueta, 'antes' => null, 'despues' => $a === null ? 'Añadido' : ($d === null ? 'Quitado' : 'Cambiado')]
                : ['campo' => $etiqueta, 'antes' => $a, 'despues' => $d];
        }

        return $cambios;
    }

    /** Valor legible para el historial: listas y botones a texto, sin HTML, recortado. */
    public static function texto(mixed $valor): ?string
    {
        if (is_bool($valor)) {
            return $valor ? 'Sí' : 'No';
        }
        if ($valor instanceof \DateTimeInterface) {
            return $valor->format('d/m/Y H:i');
        }
        if (is_array($valor)) {
            $valor = array_is_list($valor)
                ? implode(', ', array_map(fn ($v) => self::texto($v), $valor))
                : trim(($valor['texto'] ?? '').(isset($valor['url']) && $valor['url'] !== '' ? ' → '.$valor['url'] : ''));
        }
        $valor = trim(preg_replace('/\s+/', ' ', strip_tags((string) $valor)));

        return $valor === '' ? null : Str::limit($valor, 300);
    }

    /** «Título largo…» entre comillas españolas, recortado. */
    public static function cita(?string $texto): string
    {
        return '«'.Str::limit(trim((string) $texto) ?: 'sin título', 80).'»';
    }
}
