<?php

return [
    // URL base del CRM (EspoCRM), sin barra final. Vacía = integración apagada:
    // las solicitudes se siguen guardando en `contactos`, pero no se envía nada.
    'url' => rtrim((string) env('CRM_URL', ''), '/'),

    // Clave del punto de entrada del CRM (Lead Capture) de cada formulario.
    // Las genera el CRM en cada instalación; nunca van en el repositorio.
    // Un tipo sin clave no se envía (p. ej. `alumno`).
    'claves' => [
        'centro' => env('CRM_CLAVE_CENTRO'),
        'empresa' => env('CRM_CLAVE_EMPRESA'),
        'administracion' => env('CRM_CLAVE_ADMINISTRACION'),
    ],

    // Segundos de espera por petición.
    'timeout' => (int) env('CRM_TIMEOUT', 10),
];
