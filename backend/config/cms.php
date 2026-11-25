<?php

/*
|--------------------------------------------------------------------------
| Gestión web (panel de contenidos del frontoffice)
|--------------------------------------------------------------------------
*/

return [

    // Roles del panel. Quién aporta qué: el editor crea contenido y el admin además aprueba, configura
    // y gestiona usuarios. Atención al cliente (formularios y correo de info@) ya no es de Gestión web:
    // es un rol de la app DuaLab que trabaja en DuaLab Central («Primer contacto»), por la API firmada.
    'roles_panel'     => ['admin', 'editor'],
    'roles_contenido' => ['admin', 'editor'],
    'roles_admin'     => ['admin'],
    'nombres_roles'   => ['admin' => 'Administración', 'editor' => 'Edición'],

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

    // index.html compilado de la web (frontend/dist): el backend lo sirve con el texto de cada
    // página ya escrito para Google y las IA (ver HtmlPublicoService). En producción, la ruta
    // absoluta de public_html/home/index.html (WEB_INDEX_HTML en el .env del servidor).
    'web_index' => env('WEB_INDEX_HTML') ?: base_path('../frontend/dist/index.html'),

    // Rastreadores de IA que se pueden bloquear desde SEO → Buscadores de IA (robots.txt).
    // Clave = User-agent exacto; bloquear uno no afecta a Google ni a Bing.
    'bots_ia' => [
        'GPTBot'           => 'OpenAI (entrena ChatGPT)',
        'OAI-SearchBot'    => 'Búsqueda de ChatGPT',
        'ChatGPT-User'     => 'ChatGPT al abrir un enlace',
        'ClaudeBot'        => 'Anthropic (Claude)',
        'Claude-SearchBot' => 'Búsqueda de Claude',
        'PerplexityBot'    => 'Perplexity',
        'Google-Extended'  => 'Gemini (no afecta a Google Search)',
        'Applebot-Extended' => 'Apple Intelligence',
        'meta-externalagent' => 'Meta AI',
        'CCBot'            => 'Common Crawl (datos abiertos para entrenar IA)',
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

        // Datos de la organización (Yoast → «Representación en buscadores»): van en el JSON-LD
        // de todas las páginas y en llms.txt. La descripción es seo.descripcion.
        'seo.org_nombre'          => ['reglas' => ['nullable', 'string', 'max:120'], 'publico' => true],
        'seo.org_logo'            => ['reglas' => ['nullable', 'string', 'max:255', 'url_medio'], 'publico' => true],
        'seo.org_email'           => ['reglas' => ['nullable', 'email:rfc', 'max:120'], 'publico' => true],
        'seo.org_telefono'        => ['reglas' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+() .\-]{6,30}$/'], 'publico' => true],
        'seo.org_direccion'       => ['reglas' => ['nullable', 'string', 'max:160'], 'publico' => true],
        'seo.org_codigo_postal'   => ['reglas' => ['nullable', 'string', 'max:10', 'regex:/^[0-9A-Za-z \-]+$/'], 'publico' => true],
        'seo.org_localidad'       => ['reglas' => ['nullable', 'string', 'max:80'], 'publico' => true],
        'seo.org_provincia'       => ['reglas' => ['nullable', 'string', 'max:80'], 'publico' => true],
        // Perfiles en redes (LinkedIn, Instagram…): sameAs del JSON-LD
        'seo.org_redes'           => ['reglas' => ['nullable', 'array', 'max:8'], 'items' => ['url:https', 'max:255'], 'publico' => true],

        // Buscadores de IA (GEO). Decidir qué IA pueden leer la web es del admin.
        // ia_bloqueados: bots de config('cms.bots_ia') que no pueden leerla (robots.txt).
        'seo.ia_bloqueados'       => ['reglas' => ['nullable', 'array'], 'items' => ['string', 'bot_ia'], 'publico' => false, 'solo_admin' => true],
        // Presentación de DuaLab para las IA, al principio de /llms.txt
        'seo.llms_resumen'        => ['reglas' => ['nullable', 'string', 'max:3000'], 'publico' => false],

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

        // Integraciones de formularios. El aviso a la app DuaLab (captaciones en Central) no está aquí:
        // es del servidor (config/services.php → app_dualab). crm_*: aviso opcional a un CRM externo
        // (HubSpot, Pipedrive, Zapier, n8n…)
        'integraciones.crm_url'        => ['reglas' => ['nullable', 'url:https', 'max:500'], 'publico' => false],
        'integraciones.crm_activo'     => ['reglas' => ['nullable', 'boolean'],           'publico' => false],
        'integraciones.crm_secreto'    => ['reglas' => ['nullable', 'string', 'min:16', 'max:120'], 'publico' => false, 'secreto' => true],
        'integraciones.brevo_api_key'  => ['reglas' => ['nullable', 'string', 'max:200'], 'publico' => false, 'secreto' => true],
        'integraciones.brevo_lista_id' => ['reglas' => ['nullable', 'integer', 'min:1'],  'publico' => false],
        'integraciones.mailchimp_api_key' => ['reglas' => ['nullable', 'string', 'max:200'], 'publico' => false, 'secreto' => true],
        'integraciones.mailchimp_audiencia' => ['reglas' => ['nullable', 'string', 'max:40', 'regex:/^[A-Za-z0-9]+$/'], 'publico' => false],
    ],
];
