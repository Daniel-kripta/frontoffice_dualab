<?php

namespace App\Http\Requests\Integraciones;

use App\Http\Requests\Admin\EnviarCorreoRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

/**
 * Envío desde DuaLab Central por la API firmada. Llega en JSON (la firma cubre el cuerpo entero), así
 * que los adjuntos vienen en base64: se convierten en archivos subidos y pasan exactamente las mismas
 * reglas que en Gestión web (EnviarCorreoRequest: extensión y tipo real en lista blanca, tamaños…).
 */
class EnviarCorreoCentralRequest extends EnviarCorreoRequest
{
    private const MAX_ADJUNTOS_B64 = 10;

    /** Índices de adjuntos que no han llegado bien (base64 roto o vacíos): error de validación, no se ignoran */
    private array $adjuntosRotos = [];

    protected function prepareForValidation(): void
    {
        $archivos = [];
        foreach (array_values(array_slice((array) $this->input('adjuntos_b64', []), 0, self::MAX_ADJUNTOS_B64)) as $i => $a) {
            $nombre = is_array($a) ? basename((string) ($a['nombre'] ?? '')) : '';
            $contenido = is_array($a) && is_string($a['contenido'] ?? null) ? base64_decode($a['contenido'], true) : false;
            if ($contenido === false || $contenido === '') {
                $this->adjuntosRotos[] = $i;
                $contenido = '';
            }
            $ruta = tempnam(sys_get_temp_dir(), 'central');
            file_put_contents($ruta, $contenido);
            register_shutdown_function(fn () => @unlink($ruta));
            // Mismo nombre que el original: las reglas de extensión y tipo de EnviarCorreoRequest lo comprueban
            $archivos[] = new UploadedFile($ruta, $nombre !== '' ? $nombre : 'adjunto', null, null, true);
        }
        $this->files->set('adjuntos', $archivos);
        // Fuera del cuerpo (JSON): validated() ya no lo incluye y no se arrastra el base64
        $this->getInputSource()->remove('adjuntos_b64');
    }

    public function rules(): array
    {
        return parent::rules() + [
            // Versión maquetada opcional (solo desde Central): se limpia al guardarla (CorreoEnvioService)
            'html' => ['nullable', 'string', 'max:300000'],
        ];
    }

    public function after(): array
    {
        return array_merge(parent::after(), [function (Validator $v) {
            foreach ($this->adjuntosRotos as $i) {
                $v->errors()->add("adjuntos.{$i}", 'Este adjunto no ha llegado bien o está vacío.');
            }
        }]);
    }
}
