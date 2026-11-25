<?php

/*
|--------------------------------------------------------------------------
| Correo en Gestión web (buzones de Hostinger por IMAP y SMTP)
|--------------------------------------------------------------------------
| El panel no lee el buzón en directo: `correo:sincronizar` (programador, cada
| 2 minutos) copia lo nuevo a la BD y el panel lee de ahí. El original sigue en
| Hostinger; aquí solo se guardan los últimos `retencion_meses`.
| Lo que se envía desde el panel sale por la cola (EnviarCorreoJob) con la cuenta
| del buzón y se guarda una copia en su carpeta de enviados.
| La contraseña va solo en el .env: nunca en la BD ni en el navegador.
*/

return [

    'retencion_meses' => 4,

    // Mensajes que se copian como mucho en cada pasada (hosting compartido: pasadas cortas)
    'lote' => (int) env('CORREO_LOTE', 20),

    // Adjuntos recibidos más grandes no se copian: se abren en el webmail
    'max_adjunto_mb' => 15,

    // Cuerpos más grandes se recortan antes de limpiarlos (HTMLPurifier es lento con HTML enorme)
    'max_cuerpo_kb' => 768,

    // Envío: límites por correo y por día (muy por debajo del límite diario del plan de Hostinger)
    'envio' => [
        'max_destinatarios' => 20,
        'max_adjuntos'      => 10,
        'max_adjunto_mb'    => 10,
        'max_total_mb'      => 20,
        'max_por_dia'       => (int) env('CORREO_MAX_ENVIOS_DIA', 200),
        // Lista blanca de adjuntos que se pueden subir desde el panel
        'extensiones'       => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp',
            'txt', 'csv', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'zip'],
    ],

    'buzones' => [
        'info' => [
            'email'    => env('CORREO_INFO_USUARIO', 'info@dualab.es'),
            'nombre'   => env('CORREO_INFO_NOMBRE', 'DuaLab'),
            'host'     => env('CORREO_IMAP_HOST', 'imap.hostinger.com'),
            'puerto'   => (int) env('CORREO_IMAP_PUERTO', 993),
            'usuario'  => env('CORREO_INFO_USUARIO', 'info@dualab.es'),
            'password' => env('CORREO_INFO_PASSWORD'),
            // Ruta IMAP => clave en el panel (Hostinger guarda los enviados en INBOX.Sent)
            'carpetas' => ['INBOX' => 'recibidos', 'INBOX.Sent' => 'enviados'],
            'smtp'     => [
                'host'    => env('CORREO_SMTP_HOST', 'smtp.hostinger.com'),
                'puerto'  => (int) env('CORREO_SMTP_PUERTO', 465),
                // false en local para enviar a Mailpit sin TLS
                'tls'     => (bool) env('CORREO_SMTP_TLS', true),
            ],
            // En local van a false: no se escribe en el buzón real al probar
            'guardar_en_enviados' => (bool) env('CORREO_GUARDAR_ENVIADOS', true),
            // Leído / no leído en el panel → \Seen en el buzón (lo leído en el webmail siempre se trae)
            'marcar_en_buzon'     => (bool) env('CORREO_MARCAR_EN_BUZON', true),
            'firma'    => "—\nDuaLab · Formación profesional dual\ninfo@dualab.es · https://dualab.es",
        ],
    ],
];
