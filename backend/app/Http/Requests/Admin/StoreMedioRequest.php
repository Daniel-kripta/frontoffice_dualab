<?php

namespace App\Http\Requests\Admin;

use App\Services\MedioService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/** Subida a la biblioteca: extensión por contenido real (no por nombre) y tamaño por tipo. */
class StoreMedioRequest extends FormRequest
{
    public function rules(): array
    {
        $mimes = collect(config('cms.medios.tipos'))->flatMap(fn ($t) => $t['mimes'])->implode(',');
        $maxKb = collect(config('cms.medios.tipos'))->max('max_kb');

        return [
            'archivo' => ['required', 'file', "mimes:$mimes", "max:$maxKb"],
            'alt'     => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $archivo = $this->file('archivo');
            if (! $archivo || $validator->errors()->has('archivo')) {
                return;
            }
            $tipo = MedioService::tipoDe($archivo);
            if (! $tipo) {
                $validator->errors()->add('archivo', 'Tipo de archivo no permitido.');

                return;
            }
            $uso = MedioService::uso();
            if ($uso['usado'] + $archivo->getSize() > $uso['cuota']) {
                $validator->errors()->add('archivo', 'La biblioteca ha llegado a su límite de '.round($uso['cuota'] / 1024 ** 3, 1)
                    .' GB. Borra archivos que ya no se usen antes de subir más.');

                return;
            }
            $maxKb = config("cms.medios.tipos.$tipo.max_kb");
            if ($archivo->getSize() > $maxKb * 1024) {
                $validator->errors()->add('archivo', 'El archivo supera el máximo para '.$tipo.' ('.round($maxKb / 1024).' MB).');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'archivo.mimes' => 'Tipo de archivo no permitido. Imágenes JPG, PNG, WebP o GIF; vídeo MP4 o WebM; documentos PDF u Office.',
            'archivo.max'   => 'El archivo es demasiado grande.',
        ];
    }
}
