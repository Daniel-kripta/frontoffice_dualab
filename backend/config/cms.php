<?php

/*
|--------------------------------------------------------------------------
| Gestión web (panel de contenidos del frontoffice)
|--------------------------------------------------------------------------
*/

return [

    // Roles del panel. Quién aporta qué: el editor crea contenido, «contactos» solo atiende
    // los formularios y el admin además aprueba, configura y gestiona usuarios.
    'roles_panel'     => ['admin', 'editor', 'contactos'],
    'roles_contenido' => ['admin', 'editor'],
    'roles_contactos' => ['admin', 'editor', 'contactos'],
    'roles_admin'     => ['admin'],
    'nombres_roles'   => ['admin' => 'Administración', 'editor' => 'Edición', 'contactos' => 'Atención de contactos'],

    // Buzón al que también llegan los formularios (Hostinger) y su webmail
    'buzon' => [
        'email' => env('CMS_BUZON_EMAIL', 'info@dualab.es'),
        'url'   => env('CMS_BUZON_URL', 'https://mail.hostinger.com'),
    ],

    // DuaLab Central: herramienta del superadmin donde se valida lo captado (empresas y centros
    // que llegan por el webhook de Integraciones o desde la app comercial). En local: http://localhost:5179
    'consola' => [
        'nombre' => env('CMS_CONSOLA_NOMBRE', 'DuaLab Central'),
        'url'    => env('CMS_CONSOLA_URL', 'https://central.dualab.es'),
    ],
    // Tipos de formulario que la app DuaLab convierte en captación (CaptacionFormularioWebService)
    'tipos_captacion' => ['empresa', 'centro'],

    // URL pública de la web (para el sitemap, canonical y la vista previa de Google).
    'url_publica' => rtrim(env('FRONTEND_URL', 'http://localhost:5174'), '/'),

    // Idiomas que se pueden activar (lista blanca). El español es el idioma base.
    'idiomas_disponibles' => [
        'es' => 'Español',
        'en' => 'English',
        'fr' => 'Français',
        'de' => 'Deutsch',
        'it' => 'Italiano',
        'pt' => 'Português',
    ],

    // Fuentes que se pueden elegir en Apariencia. Copia de frontend/src/data/fuentes.json
    // (catálogo con familia, grosores y grupo); GestionWebTest comprueba que coinciden.
    'fuentes' => [
        'titulos' => [
            'inter', 'opensans', 'lato', 'sourcesans', 'roboto', 'nunitosans', 'worksans', 'dmsans',
            'manrope', 'notosans', 'lexend', 'poppins', 'montserrat', 'raleway', 'outfit', 'sora',
            'plusjakarta', 'figtree', 'rubik', 'mulish', 'karla', 'ibmplexsans', 'barlow', 'archivo',
            'publicsans', 'redhatdisplay', 'urbanist', 'albertsans', 'bevietnampro', 'librefranklin', 'hankengrotesk', 'nunito',
            'quicksand', 'comfortaa', 'fredoka', 'mplusrounded', 'merriweather', 'playfair', 'lora', 'sourceserif',
            'librebaskerville', 'crimsonpro', 'ptserif', 'notoserif', 'cormorant', 'ebgaramond', 'fraunces', 'bitter',
            'robotoslab', 'zillaslab', 'arvo', 'oswald', 'barlowcondensed', 'leaguespartan', 'unbounded', 'bricolage',
            'spacegrotesk', 'syne', 'bigshoulders', 'sairacondensed',
        ],
        'texto' => [
            'inter', 'opensans', 'lato', 'sourcesans', 'roboto', 'nunitosans', 'worksans', 'dmsans',
            'manrope', 'notosans', 'lexend', 'poppins', 'montserrat', 'raleway', 'outfit', 'sora',
            'plusjakarta', 'figtree', 'rubik', 'mulish', 'karla', 'ibmplexsans', 'barlow', 'archivo',
            'publicsans', 'urbanist', 'albertsans', 'bevietnampro', 'librefranklin', 'hankengrotesk', 'nunito', 'quicksand',
            'mplusrounded', 'merriweather', 'lora', 'sourceserif', 'librebaskerville', 'crimsonpro', 'ptserif', 'notoserif',
            'ebgaramond', 'bitter', 'robotoslab', 'zillaslab',
        ],
    ],

    // Colores de categoría: los de colectivo de la marca + neutros.
    'colores_categoria' => ['centros', 'empresas', 'alumnos', 'administraciones', 'azul-noche', 'violeta', 'ambar'],

    // Biblioteca de medios. SVG excluido a propósito: puede llevar JavaScript.
    'medios' => [
        'disco' => 'public',
        // Espacio total de la biblioteca (el límite real es el disco del hosting)
        'cuota_mb' => (int) env('CMS_MEDIOS_CUOTA_MB', 2048),
        // Fotos más anchas o altas se reducen al subirlas (una foto de móvil pasa de ~6 MB a <1 MB)
        'lado_max_px' => 2400,
        'calidad_jpg' => 82,
        'tipos' => [
            'imagen'    => ['mimes' => ['jpg', 'jpeg', 'png', 'webp', 'gif'], 'max_kb' => 8 * 1024],
            'video'     => ['mimes' => ['mp4', 'webm'], 'max_kb' => 60 * 1024],
            'documento' => ['mimes' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'txt', 'csv'], 'max_kb' => 20 * 1024],
        ],
    ],

    /*
    | Ajustes del sitio (tabla `ajustes`). Lista blanca: clave => [reglas, público, secreto].
    | - publico: lo devuelve /api/web/ajustes para la web pública.
    | - secreto: se guarda cifrado y el panel solo ve si está configurado.
    | - solo_admin: dentro de un grupo que también edita el editor, solo lo cambia el admin.
    */
    'ajustes' => [
        // SEO global (AIOSEO → «Ajustes generales»)
        'seo.titulo_sitio'        => ['reglas' => ['nullable', 'string', 'max:80'],  'publico' => true],
        'seo.separador'           => ['reglas' => ['nullable', 'string', 'in:|,-,–,·,•'], 'publico' => true],
        'seo.descripcion'         => ['reglas' => ['nullable', 'string', 'max:320'], 'publico' => true],
        'seo.imagen_og'           => ['reglas' => ['nullable', 'string', 'max:255', 'url_medio'], 'publico' => true],
        'seo.indexar'             => ['reglas' => ['nullable', 'boolean'],          'publico' => true],
        // Verificación de Search Console: quien la pone puede reclamar la web en Google → solo admin.
        'seo.search_console'      => ['reglas' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_\-]+$/'], 'publico' => true, 'solo_admin' => true],

        // Apariencia de la web (solo admin). Opciones cerradas: las mismas claves que
        // frontend/src/data/apariencia.js. Nada de CSS, HTML ni colores libres.
        'apariencia.paleta'         => ['reglas' => ['nullable', 'string', 'in:marca,turquesa,bosque,violeta,grafito,teja,personalizada'], 'publico' => true],
        // Paleta personalizada: dos colores en hexadecimal; la escala clara/oscura se calcula
        // en la web. `contraste_blanco` → regla ContrasteConBlanco (texto blanco legible).
        'apariencia.color_principal' => ['reglas' => ['nullable', 'required_if:ajustes.paleta,personalizada', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/', 'contraste_blanco'], 'publico' => true],
        'apariencia.color_noche'     => ['reglas' => ['nullable', 'required_if:ajustes.paleta,personalizada', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/', 'contraste_blanco'], 'publico' => true],
        // Color de los títulos (sobre fondo blanco: mismo contraste mínimo). Vacío = el de la franja.
        'apariencia.color_titulos'   => ['reglas' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/', 'contraste_blanco'], 'publico' => true],
        'apariencia.fuente_titulos' => ['reglas' => ['nullable', 'string', 'fuente_titulos'], 'publico' => true],
        'apariencia.fuente_texto'   => ['reglas' => ['nullable', 'string', 'fuente_texto'], 'publico' => true],
        'apariencia.tamano_texto'   => ['reglas' => ['nullable', 'string', 'in:normal,grande,muy-grande'], 'publico' => true],
        'apariencia.logo'           => ['reglas' => ['nullable', 'string', 'max:255', 'url_medio'], 'publico' => true],
        'apariencia.favicon'        => ['reglas' => ['nullable', 'string', 'max:255', 'url_medio'], 'publico' => true],

        // Analítica (Site Kit). La conexión con la API de Google queda para más adelante.
        'analitica.ga4_id'        => ['reglas' => ['nullable', 'string', 'regex:/^G-[A-Z0-9]{4,15}$/'], 'publico' => true],
        'analitica.gtm_id'        => ['reglas' => ['nullable', 'string', 'regex:/^GTM-[A-Z0-9]{4,12}$/'], 'publico' => true],
        'analitica.activa'        => ['reglas' => ['nullable', 'boolean'],          'publico' => true],

        // Integraciones de formularios (Flamingo + CRM/mail marketing)
        'integraciones.webhook_url'    => ['reglas' => ['nullable', 'url:https', 'max:500'], 'publico' => false],
        'integraciones.webhook_activo' => ['reglas' => ['nullable', 'boolean'],           'publico' => false],
        'integraciones.webhook_secreto' => ['reglas' => ['nullable', 'string', 'min:16', 'max:120'], 'publico' => false, 'secreto' => true],
        'integraciones.brevo_api_key'  => ['reglas' => ['nullable', 'string', 'max:200'], 'publico' => false, 'secreto' => true],
        'integraciones.brevo_lista_id' => ['reglas' => ['nullable', 'integer', 'min:1'],  'publico' => false],
        'integraciones.mailchimp_api_key' => ['reglas' => ['nullable', 'string', 'max:200'], 'publico' => false, 'secreto' => true],
        'integraciones.mailchimp_audiencia' => ['reglas' => ['nullable', 'string', 'max:40', 'regex:/^[A-Za-z0-9]+$/'], 'publico' => false],
    ],
];
