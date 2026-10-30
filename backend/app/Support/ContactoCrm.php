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

        // Sin vacíos: el CRM ignora lo que no llega, pero valida lo que llega.
        return array_filter($payload, fn ($valor) => $valor !== null && $valor !== '' && $valor !== []);
    }
}
