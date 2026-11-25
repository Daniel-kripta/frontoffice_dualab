<?php

namespace App\Http\Requests\Admin;

use App\Models\CorreoEnvio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Correo nuevo, respuesta o reenvío desde el panel (multipart: puede llevar adjuntos). */
class EnviarCorreoRequest extends FormRequest
{
    public function rules(): array
    {
        $envio = config('correo.envio');

        return [
            'buzon'               => ['nullable', Rule::in(array_keys(config('correo.buzones')))],
            'tipo'                => ['required', Rule::in(CorreoEnvio::TIPOS)],
            'correo_id'           => ['nullable', 'required_unless:tipo,nuevo', 'integer', Rule::exists('correo_mensajes', 'id')],
            // Respuesta a un formulario de la web (bandeja de Formularios)
            'contacto_id'         => ['nullable', 'integer', Rule::exists('contactos', 'id')],
            'para'                => ['required', 'array', 'min:1'],
            'para.*'              => ['required', 'email:rfc', 'max:255'],
            'cc'                  => ['nullable', 'array'],
            'cc.*'                => ['email:rfc', 'max:255'],
            'cco'                 => ['nullable', 'array'],
            'cco.*'               => ['email:rfc', 'max:255'],
            'asunto'              => ['required', 'string', 'max:255'],
            'texto'               => ['required', 'string', 'max:50000'],
            'adjuntos'            => ['nullable', 'array', 'max:'.$envio['max_adjuntos']],
            // Extensión del nombre y tipo real del contenido, los dos en la lista blanca
            'adjuntos.*'          => ['file', 'max:'.($envio['max_adjunto_mb'] * 1024),
                'extensions:'.implode(',', $envio['extensiones']), 'mimes:'.implode(',', $envio['extensiones'])],
            // Al reenviar: adjuntos del correo original que se incluyen (tienen que ser suyos)
            'reenviar_adjuntos'   => ['nullable', 'array', 'max:'.$envio['max_adjuntos']],
            'reenviar_adjuntos.*' => ['integer', Rule::exists('correo_adjuntos', 'id')
                ->where('correo_mensaje_id', (int) $this->input('correo_id'))->whereNotNull('ruta')],
        ];
    }

    public function after(): array
    {
        return [function (Validator $v) {
            $envio = config('correo.envio');
            $destinatarios = count($this->input('para', [])) + count($this->input('cc', [])) + count($this->input('cco', []));
            if ($destinatarios > $envio['max_destinatarios']) {
                $v->errors()->add('para', "Como mucho {$envio['max_destinatarios']} destinatarios por correo.");
            }

            $total = collect($this->file('adjuntos', []))->sum(fn ($f) => $f->getSize());
            if ($total > $envio['max_total_mb'] * 1024 * 1024) {
                $v->errors()->add('adjuntos', "Los adjuntos no pueden sumar más de {$envio['max_total_mb']} MB.");
            }

            $hoy = CorreoEnvio::where('buzon', $this->input('buzon', 'info'))->where('created_at', '>=', now()->startOfDay())->count();
            if ($hoy >= $envio['max_por_dia']) {
                $v->errors()->add('para', 'Se ha alcanzado el límite de correos enviados hoy desde el panel. Usa el webmail o espera a mañana.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'para.required'     => 'Añade al menos un destinatario.',
            'para.*.email'      => 'Hay una dirección de correo que no es válida.',
            'cc.*.email'        => 'Hay una dirección en CC que no es válida.',
            'cco.*.email'       => 'Hay una dirección en CCO que no es válida.',
            'adjuntos.*.max'    => 'Cada adjunto puede ocupar como mucho '.config('correo.envio.max_adjunto_mb').' MB.',
            'adjuntos.*.extensions' => 'Ese tipo de archivo no se puede adjuntar.',
            'adjuntos.*.mimes'  => 'Ese tipo de archivo no se puede adjuntar.',
        ];
    }
}
