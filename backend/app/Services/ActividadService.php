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
    public function registrar(User $usuario, string $seccion, string $accion, string $descripcion, ?string $objetoTipo = null, int|string|null $objetoRef = null): void
    {
        ActividadPanel::create([
            'user_id'        => $usuario->id,
            'usuario_nombre' => Str::limit($usuario->name, 120, ''),
            'seccion'        => $seccion,
            'accion'         => $accion,
            'descripcion'    => Str::limit($descripcion, 250),
            'objeto_tipo'    => $objetoTipo,
            'objeto_ref'     => $objetoRef === null ? null : (string) $objetoRef,
        ]);
    }

    /** «Título largo…» entre comillas españolas, recortado. */
    public static function cita(?string $texto): string
    {
        return '«'.Str::limit(trim((string) $texto) ?: 'sin título', 80).'»';
    }
}
