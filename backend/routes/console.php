<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('sanctum:prune-expired --hours=168')->weekly();
Schedule::command('queue:prune-failed --hours=48')->daily();

// Hosting compartido (Hostinger) sin workers persistentes: el cron
// `* * * * * php artisan schedule:run` vacía la cola (correos del formulario
// de contacto) cada minuto.
Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=50')
    ->everyMinute()
    ->withoutOverlapping();

// Registro de actividad del panel de gestión web: se guarda 2 años (ActividadPanel::prunable).
// Copia del correo: 4 meses (CorreoMensaje::prunable); el original sigue en Hostinger.
Schedule::command('model:prune', ['--model' => [\App\Models\ActividadPanel::class, \App\Models\CorreoMensaje::class, \App\Models\CorreoEnvio::class]])->daily();

// Bandeja de Correo del panel: copia lo nuevo de info@ (IMAP) cada 2 minutos
Schedule::command('correo:sincronizar')->everyTwoMinutes()->withoutOverlapping(10);
