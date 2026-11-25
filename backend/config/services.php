<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // API firmada para DuaLab Central (espacio «Primer contacto»: la bandeja de formularios vista
    // desde Central). El mismo valor va en el .env de la app DuaLab (FRONTOFFICE_CENTRAL_SECRETO).
    'central' => [
        'secreto' => env('CENTRAL_API_SECRETO'),
    ],

    // Aviso a la app DuaLab de cada formulario de empresa o centro → posible empresa o centro
    // (captación) en Central. Configuración fija del servidor, como la de Central: la clave es la
    // misma que FRONTOFFICE_WEBHOOK_SECRETO en el .env de la app. Ver EnviarFormularioAWebhook.
    'app_dualab' => [
        'webhook_url'     => env('APP_DUALAB_WEBHOOK_URL'),
        'webhook_secreto' => env('APP_DUALAB_WEBHOOK_SECRETO'),
    ],

    // Biblioteca de medios del panel de gestión web (misma cuenta que la app DuaLab,
    // otra carpeta). Sin estas variables, los archivos se guardan en el disco local.
    'cloudinary' => [
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
        'api_key'    => env('CLOUDINARY_API_KEY'),
        'api_secret' => env('CLOUDINARY_API_SECRET'),
        'folder'     => env('CLOUDINARY_FOLDER', 'dualab/web'),
    ],

];
