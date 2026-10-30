<?php

namespace App\Console\Commands;

use App\Jobs\EnviarContactoAlCrm;
use App\Models\Contacto;
use App\Support\ContactoCrm;
use Illuminate\Console\Command;

/**
 * Vuelve a encolar el envío al CRM de las solicitudes que no llegaron
 * (integración apagada en su momento, CRM caído, o rechazo ya corregido).
 */
class ReenviarContactosAlCrm extends Command
{
    protected $signature = 'contactos:reenviar-crm {--solo-errores : Solo las que el CRM rechazó}';

    protected $description = 'Reenvía al CRM las solicitudes de contacto que no llegaron';

    public function handle(): int
    {
        if (! config('crm.url')) {
            $this->warn('CRM_URL está vacío: la integración con el CRM está apagada.');

            return self::FAILURE;
        }

        $pendientes = Contacto::query()
            ->whereNull('crm_enviado_at')
            ->whereNotNull('accion')
            ->when($this->option('solo-errores'), fn ($q) => $q->whereNotNull('crm_error'))
            ->get()
            ->filter(fn (Contacto $c) => ContactoCrm::aplica($c) && config('crm.claves.'.$c->tipo));

        $pendientes->each(fn (Contacto $c) => EnviarContactoAlCrm::dispatch($c));

        $this->info("Solicitudes encoladas: {$pendientes->count()}.");

        return self::SUCCESS;
    }
}
