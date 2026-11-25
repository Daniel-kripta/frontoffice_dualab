<?php

namespace App\Http\Requests\Admin;

use App\Jobs\EnviarFormularioAWebhook;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/** «Probar conexión» de Gestión web → Mailing y webhook: a la app (webhook) o al CRM externo. */
class ProbarWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('administrar-web');
    }

    public function rules(): array
    {
        return ['destino' => ['nullable', Rule::in(EnviarFormularioAWebhook::DESTINOS)]];
    }
}
