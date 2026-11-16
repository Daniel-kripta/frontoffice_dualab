<?php

/*
|--------------------------------------------------------------------------
| Gestión web — páginas editables de la web pública
|--------------------------------------------------------------------------
| Lista blanca de páginas, secciones y campos que se pueden editar desde el
| panel. Lo que no está aquí no se puede guardar (ver PaginaService).
|
| `defecto` es el texto que hoy tiene la vista (frontend/src/views). La vista
| lo repite como respaldo, así que si se cambia un texto por defecto en la vista
| hay que cambiarlo también aquí para que el panel lo muestre igual.
|
| Tipos de campo:
|   texto        → una línea (max = caracteres)
|   texto_largo  → párrafo sin formato (max = caracteres)
|   boton        → { texto, url }: url relativa (/contacto?tipo=centro), ancla (#id) o https://
|   imagen       → URL de un archivo de la biblioteca de medios
|   galeria      → lista ordenada de URLs de la biblioteca (max = nº de fotos)
|
| `padre` crea la jerarquía de subpáginas que ve el panel.
*/

// Cabecera común de las páginas de colectivo (centros, empresas, alumnado, entidades).
$heroColectivo = fn (array $d) => [
    'titulo' => 'Cabecera',
    'campos' => [
        'antetitulo'        => ['tipo' => 'texto', 'etiqueta' => 'Etiqueta superior', 'max' => 60, 'defecto' => $d['antetitulo']],
        'titulo'            => ['tipo' => 'texto', 'etiqueta' => 'Título', 'max' => 120, 'defecto' => $d['titulo']],
        'destacado'         => ['tipo' => 'texto', 'etiqueta' => 'Final del título en degradado', 'max' => 60, 'defecto' => $d['destacado']],
        'descripcion'       => ['tipo' => 'texto_largo', 'etiqueta' => 'Descripción', 'max' => 500, 'defecto' => $d['descripcion'],
                                'ayuda' => 'Mientras no se edite se muestra con las palabras resaltadas en color del diseño original.'],
        'boton_principal'   => ['tipo' => 'boton', 'etiqueta' => 'Botón principal', 'defecto' => $d['boton_principal']],
        'boton_secundario'  => ['tipo' => 'boton', 'etiqueta' => 'Botón secundario', 'defecto' => $d['boton_secundario']],
        'rotulo'            => ['tipo' => 'texto', 'etiqueta' => 'Rótulo manuscrito junto a la foto', 'max' => 80, 'defecto' => $d['rotulo'],
                                'ayuda' => 'Solo se ve en escritorio.'],
        'imagen'            => ['tipo' => 'imagen', 'etiqueta' => 'Foto de la cabecera', 'defecto' => null],
        'imagen_fondo'      => ['tipo' => 'imagen', 'etiqueta' => 'Foto de fondo (tenue)', 'defecto' => null],
    ],
];

return [

    'inicio' => [
        'titulo'  => 'Inicio',
        'ruta'    => '/',
        'padre'   => null,
        'secciones' => [
            'hero' => [
                'titulo' => 'Cabecera principal',
                'campos' => [
                    'antetitulo'   => ['tipo' => 'texto', 'etiqueta' => 'Etiqueta superior', 'max' => 60, 'defecto' => 'Innovación Educativa B2B'],
                    'titulo'       => ['tipo' => 'texto', 'etiqueta' => 'Título', 'max' => 80, 'defecto' => 'Conecta talento con'],
                    'destacado'    => ['tipo' => 'texto', 'etiqueta' => 'Final del título en degradado', 'max' => 60, 'defecto' => 'retos reales'],
                    'descripcion'  => ['tipo' => 'texto_largo', 'etiqueta' => 'Descripción', 'max' => 500,
                                       'defecto' => 'DuaLab es la solución definitiva para conectar empresas con el alumnado en prácticas. Transformamos necesidades empresariales en retos académicos para impulsar el aprendizaje práctico y descubrir talento emergente.',
                                       'ayuda' => 'Mientras no se edite se muestra con las palabras resaltadas en color del diseño original.'],
                    'boton_como_funciona' => ['tipo' => 'texto', 'etiqueta' => 'Botón «Cómo funciona» (abre la explicación)', 'max' => 40, 'defecto' => 'Cómo funciona'],
                    'boton_demo'   => ['tipo' => 'boton', 'etiqueta' => 'Botón secundario', 'defecto' => ['texto' => 'Solicitar demo', 'url' => '/contacto']],
                    'fotos_carrusel' => ['tipo' => 'galeria', 'etiqueta' => 'Fotos de fondo del carrusel', 'max' => 12, 'defecto' => null,
                                         'ayuda' => 'Se van alternando detrás del texto (muy tenues). Vacío = las 8 fotos originales.'],
                ],
            ],
            'estadisticas' => [
                'titulo' => 'Cifras',
                'campos' => [
                    'cifra_1'    => ['tipo' => 'texto', 'etiqueta' => 'Cifra 1', 'max' => 12, 'defecto' => '50+'],
                    'etiqueta_1' => ['tipo' => 'texto', 'etiqueta' => 'Texto de la cifra 1', 'max' => 40, 'defecto' => 'Retos Activos'],
                    'cifra_2'    => ['tipo' => 'texto', 'etiqueta' => 'Cifra 2', 'max' => 12, 'defecto' => '3'],
                    'etiqueta_2' => ['tipo' => 'texto', 'etiqueta' => 'Texto de la cifra 2', 'max' => 40, 'defecto' => 'Niveles de Dificultad'],
                    'cifra_3'    => ['tipo' => 'texto', 'etiqueta' => 'Cifra 3', 'max' => 12, 'defecto' => '20+'],
                    'etiqueta_3' => ['tipo' => 'texto', 'etiqueta' => 'Texto de la cifra 3', 'max' => 40, 'defecto' => 'Empresas'],
                    'cifra_4'    => ['tipo' => 'texto', 'etiqueta' => 'Cifra 4', 'max' => 12, 'defecto' => '100%'],
                    'etiqueta_4' => ['tipo' => 'texto', 'etiqueta' => 'Texto de la cifra 4', 'max' => 40, 'defecto' => 'Aplicable a la vida laboral'],
                ],
            ],
            'ecosistema' => [
                'titulo' => 'Ecosistema',
                'campos' => [
                    'antetitulo'  => ['tipo' => 'texto', 'etiqueta' => 'Etiqueta superior', 'max' => 60, 'defecto' => 'Un ecosistema colaborativo'],
                    'titulo'      => ['tipo' => 'texto', 'etiqueta' => 'Título', 'max' => 100, 'defecto' => 'Todo el ecosistema de la Formación Dual,'],
                    'destacado'   => ['tipo' => 'texto', 'etiqueta' => 'Final del título en color', 'max' => 40, 'defecto' => 'conectado'],
                    'descripcion' => ['tipo' => 'texto_largo', 'etiqueta' => 'Descripción', 'max' => 500,
                                      'defecto' => 'DuaLab pone en relación a todos los agentes de la FP Dual en un entorno único, facilitando la gestión, la colaboración y la generación de oportunidades de aprendizaje y empleo.',
                                      'ayuda' => 'Mientras no se edite se muestra con las palabras resaltadas en color del diseño original.'],
                    'boton'       => ['tipo' => 'texto', 'etiqueta' => 'Botón (abre la explicación)', 'max' => 40, 'defecto' => 'Descubre en qué consiste'],
                ],
            ],
            'funcionalidades' => [
                'titulo' => 'Funcionalidades clave',
                'campos' => [
                    'antetitulo' => ['tipo' => 'texto', 'etiqueta' => 'Etiqueta superior', 'max' => 60, 'defecto' => 'Funcionalidades clave'],
                    'titulo'     => ['tipo' => 'texto', 'etiqueta' => 'Título', 'max' => 100, 'defecto' => 'Una solución integral para una Formación Dual'],
                    'destacado'  => ['tipo' => 'texto', 'etiqueta' => 'Final del título en color', 'max' => 40, 'defecto' => 'más eficiente'],
                ],
            ],
            'retos' => [
                'titulo' => 'Retos académicos',
                'campos' => [
                    'antetitulo'  => ['tipo' => 'texto', 'etiqueta' => 'Etiqueta superior', 'max' => 60, 'defecto' => 'Aprendizaje real'],
                    'titulo'      => ['tipo' => 'texto', 'etiqueta' => 'Título', 'max' => 120, 'defecto' => 'Retos académicos que generan talento y soluciones reales.',
                                      'ayuda' => 'Mientras no se edite se muestra con «talento» y «soluciones» en color.'],
                    'descripcion' => ['tipo' => 'texto_largo', 'etiqueta' => 'Descripción', 'max' => 500,
                                      'defecto' => 'Los retos de DuaLab acercan al alumnado a proyectos planteados por empresas, fomentando la innovación, el trabajo en equipo y el desarrollo de competencias clave.'],
                    'boton_centro'   => ['tipo' => 'boton', 'etiqueta' => 'Botón centros', 'defecto' => ['texto' => 'Inscribe tu centro', 'url' => '/contacto?tipo=centro']],
                    'boton_empresa'  => ['tipo' => 'boton', 'etiqueta' => 'Botón empresas', 'defecto' => ['texto' => 'Participa como empresa', 'url' => '/contacto?tipo=empresa']],
                    'boton_explorar' => ['tipo' => 'boton', 'etiqueta' => 'Botón explorar', 'defecto' => ['texto' => 'Explora retos', 'url' => '/familias']],
                    'imagen'      => ['tipo' => 'imagen', 'etiqueta' => 'Foto', 'defecto' => null],
                ],
            ],
            'agentes' => [
                'titulo' => 'Una solución para cada agente',
                'campos' => [
                    'antetitulo' => ['tipo' => 'texto', 'etiqueta' => 'Etiqueta superior', 'max' => 60, 'defecto' => 'Cuatro agentes, un mismo objetivo'],
                    'titulo'     => ['tipo' => 'texto', 'etiqueta' => 'Título', 'max' => 100, 'defecto' => 'Una solución para cada agente'],
                ],
            ],
            'plataforma' => [
                'titulo' => 'Plataforma',
                'campos' => [
                    'antetitulo'  => ['tipo' => 'texto', 'etiqueta' => 'Etiqueta superior', 'max' => 60, 'defecto' => 'Plataforma DuaLab'],
                    'titulo'      => ['tipo' => 'texto', 'etiqueta' => 'Título', 'max' => 100, 'defecto' => 'Toda la gestión en un mismo lugar',
                                      'ayuda' => 'Mientras no se edite se muestra con «gestión» y «mismo lugar» en color.'],
                    'descripcion' => ['tipo' => 'texto_largo', 'etiqueta' => 'Descripción', 'max' => 500,
                                      'defecto' => 'Una plataforma intuitiva y completa para gestionar prácticas, tutores, documentación, comunicaciones y mucho más, con información siempre actualizada y accesible para todos los agentes.'],
                    'boton_demo'  => ['tipo' => 'boton', 'etiqueta' => 'Botón principal', 'defecto' => ['texto' => 'Solicitar demo', 'url' => '/contacto']],
                ],
            ],
            'galeria' => [
                'titulo' => 'Galería',
                'campos' => [
                    'etiqueta'  => ['tipo' => 'texto', 'etiqueta' => 'Etiqueta superior', 'max' => 40, 'defecto' => 'Nuestra galería'],
                    'titulo'    => ['tipo' => 'texto', 'etiqueta' => 'Título', 'max' => 80, 'defecto' => 'Hablamos con experiencia'],
                    'subtitulo' => ['tipo' => 'texto', 'etiqueta' => 'Subtítulo', 'max' => 160, 'defecto' => 'Momentos reales de formación, retos y trabajo en equipo'],
                    'fotos'     => ['tipo' => 'galeria', 'etiqueta' => 'Fotos de la galería', 'max' => 40, 'defecto' => null,
                                    'ayuda' => 'Las 3 primeras se ven en la página; el resto, al pulsar «Ver más». Vacío = las fotos originales.'],
                ],
            ],
            'familias' => [
                'titulo' => 'Familias profesionales',
                'campos' => [
                    'antetitulo' => ['tipo' => 'texto', 'etiqueta' => 'Etiqueta superior', 'max' => 40, 'defecto' => 'Formación'],
                    'subtitulo'  => ['tipo' => 'texto', 'etiqueta' => 'Subtítulo', 'max' => 160, 'defecto' => 'Retos organizados en áreas clave del mercado laboral'],
                    'boton'      => ['tipo' => 'boton', 'etiqueta' => 'Botón', 'defecto' => ['texto' => 'Ver familias', 'url' => '/familias']],
                ],
            ],
        ],
    ],

    'centros' => [
        'titulo'  => 'Centros Educativos',
        'ruta'    => '/centros',
        'padre'   => 'inicio',
        'secciones' => [
            'hero' => $heroColectivo([
                'antetitulo'       => 'Centros educativos',
                'titulo'           => 'La Formación Dual más conectada,',
                'destacado'        => 'simple y eficaz',
                'descripcion'      => 'DuaLab es la plataforma que ayuda a tu centro a gestionar la Formación Dual de forma integral, conectando alumnado, empresas y tutoría, y facilitando el seguimiento, la comunicación y la evaluación de todo el proceso.',
                'boton_principal'  => ['texto' => 'Registrar mi centro', 'url' => '/contacto?tipo=centro'],
                'boton_secundario' => ['texto' => 'Solicitar demo', 'url' => '/contacto?tipo=centro'],
                'rotulo'           => 'Conectando talento con oportunidades reales',
            ]),
        ],
    ],

    'empresas' => [
        'titulo'  => 'Empresas',
        'ruta'    => '/empresas-asociadas',
        'padre'   => 'inicio',
        'secciones' => [
            'hero' => $heroColectivo([
                'antetitulo'       => 'Empresas',
                'titulo'           => 'Conecta tu empresa con el talento',
                'destacado'        => 'del futuro',
                'descripcion'      => 'Colabora con centros educativos, participa en la Formación Dual y convierte los retos de tu empresa en oportunidades de aprendizaje.',
                'boton_principal'  => ['texto' => 'Registrar mi empresa', 'url' => '/contacto?tipo=empresa'],
                'boton_secundario' => ['texto' => 'Solicitar demo', 'url' => '/contacto?tipo=empresa'],
                'rotulo'           => 'Talento real para retos reales',
            ]),
        ],
    ],

    'alumnado' => [
        'titulo'  => 'Alumnado',
        'ruta'    => '/para-alumnado',
        'padre'   => 'inicio',
        'secciones' => [
            'hero' => $heroColectivo([
                'antetitulo'       => 'Alumnado',
                'titulo'           => 'Tu talento, conectado con',
                'destacado'        => 'tu futuro',
                'descripcion'      => 'Aprende con retos reales, conecta con el mundo profesional y sigue tu Formación Dual en un mismo lugar.',
                'boton_principal'  => ['texto' => 'Explorar retos', 'url' => '/familias'],
                'boton_secundario' => ['texto' => 'Solicitar información', 'url' => '/contacto'],
                'rotulo'           => 'Aprende, conecta y crece',
            ]),
        ],
    ],

    'entidades' => [
        'titulo'  => 'Entidades',
        'ruta'    => '/entidades',
        'padre'   => 'inicio',
        'secciones' => [
            'hero' => $heroColectivo([
                'antetitulo'       => 'Instituciones y administraciones',
                'titulo'           => 'Impulsamos la Formación Dual desde',
                'destacado'        => 'las Administraciones',
                'descripcion'      => 'DuaLab es la plataforma que conecta centros, empresas y alumnado, facilitando la planificación, gestión, seguimiento y evaluación de los programas de Formación Profesional Dual de forma eficiente, transparente y con impacto real en el territorio.',
                'boton_principal'  => ['texto' => 'Contáctanos', 'url' => '/contacto?tipo=administracion'],
                'boton_secundario' => ['texto' => 'Solicitar demo', 'url' => '/contacto?tipo=administracion'],
                'rotulo'           => 'Formación Dual para territorios más competitivos',
            ]),
        ],
    ],

    'familias' => [
        'titulo'  => 'Familias Profesionales',
        'ruta'    => '/familias',
        'padre'   => null,
        'secciones' => [
            'hero' => [
                'titulo' => 'Cabecera',
                'campos' => [
                    'antetitulo'   => ['tipo' => 'texto', 'etiqueta' => 'Etiqueta superior', 'max' => 40, 'defecto' => 'PLATAFORMA'],
                    'titulo'       => ['tipo' => 'texto', 'etiqueta' => 'Título', 'max' => 80, 'defecto' => 'Familias Profesionales'],
                    'subtitulo'    => ['tipo' => 'texto_largo', 'etiqueta' => 'Subtítulo', 'max' => 300, 'defecto' => 'Retos reales de empresas, organizados por área de conocimiento.'],
                    'imagen_fondo' => ['tipo' => 'imagen', 'etiqueta' => 'Foto de fondo (tenue)', 'defecto' => null],
                ],
            ],
        ],
    ],

    'noticias' => [
        'titulo'  => 'Noticias',
        'ruta'    => '/noticias',
        'padre'   => null,
        'secciones' => [
            'hero' => [
                'titulo' => 'Cabecera del blog',
                'campos' => [
                    'antetitulo'   => ['tipo' => 'texto', 'etiqueta' => 'Etiqueta superior', 'max' => 40, 'defecto' => 'ACTUALIDAD'],
                    'titulo'       => ['tipo' => 'texto', 'etiqueta' => 'Título', 'max' => 80, 'defecto' => 'Noticias'],
                    'subtitulo'    => ['tipo' => 'texto_largo', 'etiqueta' => 'Subtítulo', 'max' => 300, 'defecto' => 'Novedades, casos de éxito y eventos de DuaLab y el ecosistema de la formación profesional dual.'],
                    'imagen_fondo' => ['tipo' => 'imagen', 'etiqueta' => 'Foto de fondo (tenue)', 'defecto' => null],
                ],
            ],
        ],
    ],

    'contacto' => [
        'titulo'  => 'Contacto',
        'ruta'    => '/contacto',
        'padre'   => null,
        'secciones' => [],
    ],

    'solicitar-demo' => [
        'titulo'  => 'Solicitar demo de un proyecto',
        'ruta'    => '/retos-destacados/:id/solicitar-demo',
        'padre'   => 'contacto',
        'secciones' => [],
    ],

    // Bloques que se repiten en varias páginas.
    'comun' => [
        'titulo'  => 'Bloques comunes',
        'ruta'    => null,
        'padre'   => null,
        'secciones' => [
            'cta_final' => [
                'titulo' => 'Franja final «Transforma la Formación Dual»',
                'campos' => [
                    'titulo'      => ['tipo' => 'texto', 'etiqueta' => 'Título', 'max' => 100, 'defecto' => 'Transforma la Formación Dual con'],
                    'destacado'   => ['tipo' => 'texto', 'etiqueta' => 'Final del título en color', 'max' => 40, 'defecto' => 'DuaLab'],
                    'descripcion' => ['tipo' => 'texto_largo', 'etiqueta' => 'Descripción', 'max' => 300, 'defecto' => 'Conecta talento, impulsa la colaboración y genera un impacto real en la educación y en la sociedad'],
                    'boton_principal' => ['tipo' => 'texto', 'etiqueta' => 'Botón principal (lleva a contacto)', 'max' => 60, 'defecto' => 'Agenda una reunión con nuestro equipo'],
                    'boton_noticias'  => ['tipo' => 'boton', 'etiqueta' => 'Botón secundario', 'defecto' => ['texto' => 'Ver noticias', 'url' => '/noticias']],
                    'imagen'      => ['tipo' => 'imagen', 'etiqueta' => 'Foto', 'defecto' => null],
                ],
            ],
        ],
    ],
];
