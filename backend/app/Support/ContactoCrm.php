<?php

namespace App\Support;

use App\Models\Contacto;

/**
 * Convierte una solicitud de contacto en el cuerpo que espera el CRM
 * (EspoCRM, Lead Capture). Las listas (familias, sector, tamaño, colaboración,
 * tipo de entidad, ámbito, intereses) viajan tal cual: el CRM guarda los mismos
 * valores que ContactoOpciones. Solo se traducen el tipo y la acción.
 */
final class ContactoCrm
{
    /** `tipo` del formulario => Ruta del CRM. */
    public const RUTAS = [
        'centro' => 'Centro FP',
        'empresa' => 'Empresa',
        'administracion' => 'Administración',
    ];

    /** `accion` del formulario => Tipo de solicitud del CRM. */
    public const TIPOS_SOLICITUD = [
        'registro' => 'Registro',
        'info' => 'Más información',
        'demo' => 'Demo',
    ];

    /** Campo de `datos` => campo del CRM. */
    private const CAMPOS = [
        'direccion' => 'addressStreet',
        'municipio' => 'addressCity',
        'provincia' => 'addressState',
        'mensaje' => 'need',
        'cif' => 'cif',
        'familias' => 'professionalFamilies',
        'sector' => 'sector',
        'tamano' => 'companySize',
        'colabora' => 'collaboratesWithCenters',
        'tipo_entidad' => 'orgType',
        'ambito' => 'territorialScope',
        'intereses' => 'interests',
    ];

    /** Clave de `origen` => campo del CRM (el CRM decide la procedencia a partir de ellos). */
    private const ORIGEN = [
        'utm_source' => 'utmSource',
        'utm_medium' => 'utmMedium',
        'utm_campaign' => 'utmCampaign',
        'utm_content' => 'utmContent',
        'utm_term' => 'utmTerm',
        'landing' => 'landingPage',
        'referrer' => 'referrerUrl',
    ];

    /** Solo van al CRM los formularios por agente (con `accion`) de un tipo con ruta. */
    public static function aplica(Contacto $c): bool
    {
        return isset(self::RUTAS[$c->tipo], self::TIPOS_SOLICITUD[$c->accion ?? '']);
    }

    public static function payload(Contacto $c): array
    {
        $datos = $c->datos ?? [];

        $payload = [
            'firstName' => $c->nombre,
            'lastName' => $c->apellidos,
            'title' => $c->cargo,
            'emailAddress' => $c->email,
            'phoneNumber' => $c->telefono,
            'accountName' => ContactoResumen::organizacion($c),
            'route' => self::RUTAS[$c->tipo],
            'requestType' => self::TIPOS_SOLICITUD[$c->accion],
        ];

        foreach (self::CAMPOS as $origen => $destino) {
            $payload[$destino] = $datos[$origen] ?? null;
        }

        // Origen (si el enlace lo traía) y consentimiento: sin marcar viaja `false`, la fecha solo si se marcó.
        foreach (self::ORIGEN as $origen => $destino) {
            $payload[$destino] = $c->origen[$origen] ?? null;
        }
        $payload['marketingConsent'] = $c->marketing_consent_at !== null;
        $payload['marketingConsentAt'] = $c->marketing_consent_at?->utc()->format('Y-m-d H:i:s');

        // Sin vacíos: el CRM ignora lo que no llega, pero valida lo que llega.
        return array_filter($payload, fn ($valor) => $valor !== null && $valor !== '' && $valor !== []);
    }
}
