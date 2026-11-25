<?php

namespace App\Http\Resources\Admin;

use App\Models\Contacto;
use App\Services\CorreoSincronizacionService;
use App\Support\ContactoResumen;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Correo de la bandeja. El cuerpo y los adjuntos solo en el detalle. */
class CorreoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Ficha: en Gestión web y en la API de DuaLab Central («Primer contacto»)
        $detalle = $request->routeIs('admin.correo.show', 'central.correo.show', 'central.correo.update');

        return [
            'id'             => $this->id,
            'buzon'          => $this->buzon,
            'de_email'       => $this->de_email,
            'de_nombre'      => $this->de_nombre,
            'asunto'         => $this->asunto,
            'extracto'       => $this->extracto,
            'fecha'          => $this->fecha,
            'estado'         => $this->estado,
            'leido_en'       => $this->leido_en,
            'tiene_adjuntos' => $this->tiene_adjuntos,
            'carpeta'        => CorreoSincronizacionService::claveCarpeta($this->buzon, $this->carpeta),
            'para'           => $this->para ?? [],
            'message_id'     => $this->when($detalle, $this->message_id),
            // Para citarlo al responder o reenviar, también si el correo solo trae HTML
            // (sin espacios de maquetación ni líneas vacías seguidas)
            'texto_citable'  => $this->when($detalle, fn () => trim(preg_replace(["/[ \t\x{00A0}]+/u", "/ *\n */", "/\n{3,}/"], [' ', "\n", "\n\n"],
                $this->texto ?? html_entity_decode(strip_tags(preg_replace('/<br\s*\/?>|<\/(p|div|tr|h\d|li)>/i', "\n", (string) $this->html)), ENT_QUOTES | ENT_HTML5)))),
            'cc'             => $this->when($detalle, $this->cc ?? []),
            'responder_a'    => $this->when($detalle, $this->responder_a ?? []),
            'texto'          => $this->when($detalle, $this->texto),
            'html'           => $this->when($detalle, $this->html),
            // Si quien escribe ya nos contactó por un formulario de la web: qué es y cuál fue el último
            'contacto'       => $this->when($detalle, fn () => ($c = Contacto::whereRaw('LOWER(email) = ?', [strtolower($this->de_email)])->latest()->first(['id', 'tipo', 'accion', 'created_at']))
                ? ['id' => $c->id, 'tipo' => $c->tipo, 'tipo_nombre' => ContactoResumen::TIPOS[$c->tipo] ?? $c->tipo, 'fecha' => $c->created_at]
                : null),
            'adjuntos'       => $this->when($detalle, fn () => $this->adjuntos->map(fn ($a) => [
                'id'         => $a->id,
                'nombre'     => $a->nombre,
                'tipo'       => $a->tipo,
                'tamano'     => $a->tamano,
                'en_linea'   => $a->en_linea,
                'disponible' => $a->ruta !== null,
            ])),
        ];
    }
}
