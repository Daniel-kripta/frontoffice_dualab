<?php

namespace App\Http\Resources\Integraciones;

use App\Http\Resources\Admin\FormularioResource;
use Illuminate\Http\Request;

/**
 * Formulario tal como lo ve DuaLab Central (espacio «Primer contacto»): los mismos campos que la
 * bandeja de Gestión web y, en la ficha, también los datos del formulario y las respuestas enviadas.
 */
class FormularioCentralResource extends FormularioResource
{
    public function toArray(Request $request): array
    {
        $base = parent::toArray($request);
        if (!$request->routeIs('central.formularios.show', 'central.formularios.update')) {
            return $base;
        }

        return array_merge($base, [
            'datos'      => $this->datos ?? (object) [],
            'respuestas' => $this->respuestas->map(fn ($e) => [
                'id'     => $e->id,
                'asunto' => $e->asunto,
                'estado' => $e->estado,
                'fecha'  => $e->enviado_en ?? $e->created_at,
                'por'    => $e->usuario?->name,
            ]),
        ]);
    }
}
