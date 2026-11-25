<?php
/**
 * Ok, glad you are here
 * first we get a config instance, and set the settings
 * $config = HTMLPurifier_Config::createDefault();
 * $config->set('Core.Encoding', $this->config->get('purifier.encoding'));
 * $config->set('Cache.SerializerPath', $this->config->get('purifier.cachePath'));
 * if ( ! $this->config->get('purifier.finalize')) {
 *     $config->autoFinalize = false;
 * }
 * $config->loadArray($this->getConfig());
 *
 * You must NOT delete the default settings
 * anything in settings should be compacted with params that needed to instance HTMLPurifier_Config.
 *
 * @link http://htmlpurifier.org/live/configdoc/plain.html
 */

return [
    'encoding'           => 'UTF-8',
    'finalize'           => true,
    'ignoreNonStrings'   => false,
    'cachePath'          => storage_path('app/purifier'),
    'cacheFileMode'      => 0755,
    'settings'      => [
        'default' => [
            'HTML.Doctype'             => 'HTML 4.01 Transitional',
            'HTML.Allowed'             => 'div,b,strong,i,em,u,a[href|title],ul,ol,li,p[style],br,span[style],img[width|height|alt|src]',
            'CSS.AllowedProperties'    => 'font,font-size,font-weight,font-style,font-family,text-decoration,padding-left,color,background-color,text-align',
            'AutoFormat.AutoParagraph' => true,
            'AutoFormat.RemoveEmpty'   => true,
        ],
        // Contenido de las entradas del blog (CKEditor 5 del panel de gestión web).
        // Lista blanca: solo lo que la barra del editor puede producir. Sin style,
        // sin iframes ni scripts; enlaces solo http(s)/mailto/tel.
        'cms' => [
            'HTML.Doctype'             => 'HTML 4.01 Transitional',
            'HTML.Allowed'             => 'h2,h3,h4,p,br,hr,strong,b,em,i,u,s,sub,sup,blockquote,code,pre,'
                                        .'a[href|title|target|rel],ul,ol,li,'
                                        .'figure[class],figcaption,img[src|alt|width|height],'
                                        .'table,thead,tbody,tr,th[colspan|rowspan],td[colspan|rowspan]',
            'URI.AllowedSchemes'       => ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true],
            'Attr.AllowedFrameTargets' => ['_blank'],
            'Attr.AllowedClasses'      => ['image', 'image-style-side', 'image_resized', 'table'],
            'HTML.TargetNoopener'      => true,
            'HTML.TargetNoreferrer'    => true,
            'AutoFormat.RemoveEmpty'   => true,
        ],
        // Correos recibidos (bandeja de Correo del panel). Más amplio que `cms` porque los correos
        // se maquetan con tablas y estilos en línea, pero sin scripts, formularios, iframes ni
        // <style>. El panel además los muestra en un iframe aislado con CSP que bloquea las
        // imágenes externas hasta que se pide verlas (píxeles de seguimiento).
        'correo' => [
            'HTML.Doctype'             => 'XHTML 1.0 Transitional',
            'HTML.Allowed'             => 'div[style|align],span[style],p[style|align],br,hr,center,font[color|size|face],'
                                        .'h1[style],h2[style],h3[style],h4[style],h5[style],h6[style],'
                                        .'strong,b,em,i,u,s,strike,sub,sup,small,big,blockquote[style],pre,code,'
                                        .'a[href|title|style],ul[style],ol[style],li[style],dl,dt,dd,'
                                        .'img[src|alt|width|height|style|border],'
                                        .'table[style|width|border|cellpadding|cellspacing|align|bgcolor],thead,tbody,tfoot,'
                                        .'tr[style|align|valign|bgcolor],'
                                        .'td[style|width|height|colspan|rowspan|align|valign|bgcolor],'
                                        .'th[style|width|colspan|rowspan|align|valign|bgcolor]',
            'CSS.AllowedProperties'    => 'font,font-size,font-weight,font-style,font-family,line-height,color,background-color,'
                                        .'text-align,text-decoration,vertical-align,width,max-width,height,'
                                        .'margin,margin-top,margin-bottom,margin-left,margin-right,'
                                        .'padding,padding-top,padding-bottom,padding-left,padding-right,'
                                        .'border,border-top,border-bottom,border-left,border-right,border-collapse,'
                                        // Correos con diseño de la app (franjas de color, cabecera). Solo propiedades que
                                        // HTMLPurifier conoce: con una desconocida avisa y la limpieza falla para todos
                                        .'background,letter-spacing,white-space',
            'URI.AllowedSchemes'       => ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true],
            'HTML.TargetBlank'         => true,
            'HTML.TargetNoopener'      => true,
            'HTML.TargetNoreferrer'    => true,
        ],
        'test'    => [
            'Attr.EnableID' => 'true',
        ],
        "youtube" => [
            "HTML.SafeIframe"      => 'true',
            "URI.SafeIframeRegexp" => "%^(http://|https://|//)(www.youtube.com/embed/|player.vimeo.com/video/)%",
        ],
        'custom_definition' => [
            'id'  => 'html5-definitions',
            'rev' => 1,
            'debug' => false,
            'elements' => [
                // http://developers.whatwg.org/sections.html
                ['section', 'Block', 'Flow', 'Common'],
                ['nav',     'Block', 'Flow', 'Common'],
                ['article', 'Block', 'Flow', 'Common'],
                ['aside',   'Block', 'Flow', 'Common'],
                ['header',  'Block', 'Flow', 'Common'],
                ['footer',  'Block', 'Flow', 'Common'],
				
				// Content model actually excludes several tags, not modelled here
                ['address', 'Block', 'Flow', 'Common'],
                ['hgroup', 'Block', 'Required: h1 | h2 | h3 | h4 | h5 | h6', 'Common'],
				
				// http://developers.whatwg.org/grouping-content.html
                ['figure', 'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common'],
                ['figcaption', 'Inline', 'Flow', 'Common'],
				
				// http://developers.whatwg.org/the-video-element.html#the-video-element
                ['video', 'Block', 'Optional: (source, Flow) | (Flow, source) | Flow', 'Common', [
                    'src' => 'URI',
					'type' => 'Text',
					'width' => 'Length',
					'height' => 'Length',
					'poster' => 'URI',
					'preload' => 'Enum#auto,metadata,none',
					'controls' => 'Bool',
                ]],
                ['source', 'Block', 'Flow', 'Common', [
					'src' => 'URI',
					'type' => 'Text',
                ]],

				// http://developers.whatwg.org/text-level-semantics.html
                ['s',    'Inline', 'Inline', 'Common'],
                ['var',  'Inline', 'Inline', 'Common'],
                ['sub',  'Inline', 'Inline', 'Common'],
                ['sup',  'Inline', 'Inline', 'Common'],
                ['mark', 'Inline', 'Inline', 'Common'],
                ['wbr',  'Inline', 'Empty', 'Core'],
				
				// http://developers.whatwg.org/edits.html
                ['ins', 'Block', 'Flow', 'Common', ['cite' => 'URI', 'datetime' => 'CDATA']],
                ['del', 'Block', 'Flow', 'Common', ['cite' => 'URI', 'datetime' => 'CDATA']],
            ],
            'attributes' => [
                ['iframe', 'allowfullscreen', 'Bool'],
                ['table', 'height', 'Text'],
                ['td', 'border', 'Text'],
                ['th', 'border', 'Text'],
                ['tr', 'width', 'Text'],
                ['tr', 'height', 'Text'],
                ['tr', 'border', 'Text'],
            ],
        ],
        'custom_attributes' => [
            ['a', 'target', 'Enum#_blank,_self,_target,_top'],
        ],
        'custom_elements' => [
            ['u', 'Inline', 'Inline', 'Common'],
        ],
    ],

];
