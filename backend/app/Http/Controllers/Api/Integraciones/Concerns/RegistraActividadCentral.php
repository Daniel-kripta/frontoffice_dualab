<?php

namespace App\Http\Controllers\Api\Integraciones\Concerns;

use App\Models\ActividadPanel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Registro de actividad del panel para lo que se hace desde DuaLab Central (no hay usuario del panel). */
trait RegistraActividadCentral
{
    private function registrarCentral(Request $request, string $seccion, string $accion, string $descripcion, string $objetoTipo, int $ref): void
    {
        // Nombre del superadmin, firmado en la cabecera (VerificarFirmaCentral)
        $quien = trim(strip_tags((string) $request->header('X-DuaLab-Usuario', ''))) ?: 'superadmin';

        ActividadPanel::create([
            'user_id'        => null,
            'usuario_nombre' => Str::limit('Central · '.$quien, 120, ''),
            'seccion'        => $seccion,
            'accion'         => $accion,
            'descripcion'    => Str::limit($descripcion, 250),
            'objeto_tipo'    => $objetoTipo,
            'objeto_ref'     => (string) $ref,
        ]);
    }
}
