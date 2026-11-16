<?php

namespace App\Http\Requests\Admin;

use App\Rules\ContrasteConBlanco;
use App\Rules\UrlMedio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/** Solo claves del grupo pedido (seo, analitica, integraciones) de la lista blanca de config/cms.php, sin el prefijo del grupo. */
class UpdateAjustesRequest extends FormRequest
{
    /** Autoriza antes de validar: seo lo editan editores; analitica e integraciones, solo admin. */
    public function authorize(): bool
    {
        $grupo = $this->route('grupo');
        abort_unless(in_array($grupo, ['seo', 'analitica', 'integraciones', 'apariencia'], true), 404);

        return Gate::allows($grupo === 'seo' ? 'gestionar-web' : 'administrar-web');
    }

    public function rules(): array
    {
        $grupo  = $this->route('grupo');
        $esAdmin = Gate::allows('administrar-web');
        $claves = collect(config('cms.ajustes'))
            ->filter(fn ($d, $k) => str_starts_with($k, "$grupo.") && ($esAdmin || empty($d['solo_admin'])));

        // Sin el prefijo del grupo: { titulo_sitio: … } → seo.titulo_sitio
        $nombres = $claves->keys()->map(fn ($k) => substr($k, strlen($grupo) + 1));
        $reglas  = ['ajustes' => ['required', 'array:'.$nombres->implode(',')]];
        foreach ($claves as $clave => $def) {
            // 'url_medio' en config (debe ser serializable para config:cache) → regla UrlMedio
            $reglas['ajustes.'.substr($clave, strlen($grupo) + 1)] = array_map(
                fn ($r) => match ($r) {
                    'url_medio'        => new UrlMedio,
                    'contraste_blanco' => new ContrasteConBlanco,
                    'fuente_titulos'   => Rule::in(config('cms.fuentes.titulos')),
                    'fuente_texto'     => Rule::in(config('cms.fuentes.texto')),
                    default            => $r,
                }, $def['reglas']);
        }

        return $reglas;
    }

    public function messages(): array
    {
        return [
            'ajustes.*.regex'       => 'El formato no es válido.',
            'ajustes.*.in'          => 'Esa opción no está disponible.',
            'ajustes.*.required_if' => 'Elige este color para la paleta personalizada.',
        ];
    }
}
