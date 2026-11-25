<?php

namespace App\Http\Resources\Admin;

use App\Services\BandejaFormulariosService as Bandeja;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Envío de un formulario web (tabla `contactos`) tal como lo ve la bandeja del panel. */
class FormularioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $formulario = Bandeja::formularioDe($this->resource);

        return [
            'id'                => $this->id,
            'formulario'        => $formulario,
            'tipo'              => $this->tipo,
            'formulario_nombre' => Bandeja::FORMULARIOS[$formulario] ?? $this->tipo,
            'accion'            => $this->accion,
            'accion_nombre'     => Bandeja::ACCIONES[$this->accion] ?? null,
            'nombre'            => $this->nombre,
            'apellidos'         => $this->apellidos,
            'email'             => $this->email,
            'telefono'          => $this->telefono,
            'cargo'             => $this->cargo,
            'datos'             => $this->when($request->routeIs('admin.formularios.show'), $this->datos ?? (object) []),
            'pagina_origen'     => $this->pagina_origen,
            'estado'            => $this->estado,
            'leido_en'          => $this->leido_en,
            'created_at'        => $this->created_at,
            // Correos que se le han enviado desde el panel (Correo → responder a un formulario)
            'respuestas'        => $this->when($request->routeIs('admin.formularios.show'), fn () => $this->respuestas->map(fn ($e) => [
                'id'         => $e->id,
                'asunto'     => $e->asunto,
                'estado'     => $e->estado,
                'fecha'      => $e->enviado_en ?? $e->created_at,
                'por'        => $e->usuario?->name,
            ])),
        ];
    }
}
