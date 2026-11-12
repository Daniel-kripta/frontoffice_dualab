<?php

namespace Tests\Feature;

use App\Models\Contacto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Envío de las solicitudes de contacto al CRM (EnviarContactoAlCrm). */
class ContactoCrmTest extends TestCase
{
    use RefreshDatabase;

    private const CRM = 'https://crm.test';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'crm.url' => self::CRM,
            'crm.claves' => ['centro' => 'clave-centro', 'empresa' => 'clave-empresa', 'administracion' => 'clave-admin'],
        ]);
    }

    private function persona(string $email): array
    {
        return [
            'accion' => 'info', 'nombre' => 'Laura', 'apellidos' => 'Rodríguez Pérez', 'email' => $email,
            'telefono' => '+34 612 345 678', 'cargo' => 'Responsable de RRHH',
        ];
    }

    private function empresa(string $email = 'laura@empresa.test'): array
    {
        return $this->persona($email) + [
            'tipo' => 'empresa', 'empresa_nombre' => 'Tecnología Canaria S.L.', 'cif' => 'B12345678',
            'sector' => 'Tecnología', 'provincia' => 'Las Palmas', 'municipio' => 'Telde',
            'direccion' => 'Calle Ejemplo 1', 'tamano' => 'pequena', 'colabora' => 'valorando',
            'intereses' => ['plantear_retos', 'casos_exito'], 'mensaje' => 'Queremos plantear un reto.',
        ];
    }

    public function test_el_formulario_de_empresa_llega_al_crm_con_los_mismos_valores(): void
    {
        Http::fake([self::CRM.'/*' => Http::response('true')]);

        $this->postJson('/api/contacto', $this->empresa())->assertCreated();

        Http::assertSent(function (Request $request) {
            return $request->url() === self::CRM.'/api/v1/LeadCapture/clave-empresa'
                && $request->data() == [
                    'firstName' => 'Laura', 'lastName' => 'Rodríguez Pérez', 'title' => 'Responsable de RRHH',
                    'emailAddress' => 'laura@empresa.test', 'phoneNumber' => '+34 612 345 678',
                    'accountName' => 'Tecnología Canaria S.L.',
                    'route' => 'Empresa', 'requestType' => 'Más información',
                    'addressStreet' => 'Calle Ejemplo 1', 'addressCity' => 'Telde', 'addressState' => 'Las Palmas',
                    'need' => 'Queremos plantear un reto.', 'cif' => 'B12345678',
                    'sector' => 'Tecnología', 'companySize' => 'pequena', 'collaboratesWithCenters' => 'valorando',
                    'interests' => ['plantear_retos', 'casos_exito'],
                    // Sin casilla marcada ni origen en el enlace: viaja "no" y nada más
                    'marketingConsent' => false,
                ];
        });

        $contacto = Contacto::sole();
        $this->assertNotNull($contacto->crm_enviado_at);
        $this->assertNull($contacto->crm_error);
    }

    public function test_el_formulario_de_centro_envia_sus_familias(): void
    {
        Http::fake([self::CRM.'/*' => Http::response('true')]);

        $this->postJson('/api/contacto', ['accion' => 'registro'] + $this->persona('direccion@centro.test') + [
            'tipo' => 'centro', 'centro_nombre' => 'IES Sierra Norte', 'direccion' => 'Calle Ejemplo 2',
            'municipio' => 'Arucas', 'provincia' => 'Las Palmas',
            'familias' => ['Informática y Comunicaciones', 'Sanidad'],
        ])->assertCreated();

        Http::assertSent(fn (Request $request) => $request->url() === self::CRM.'/api/v1/LeadCapture/clave-centro'
            && $request['route'] === 'Centro FP'
            && $request['requestType'] === 'Registro'
            && $request['accountName'] === 'IES Sierra Norte'
            && $request['professionalFamilies'] === ['Informática y Comunicaciones', 'Sanidad']
            && ! isset($request['need'], $request['cif']));
    }

    public function test_el_formulario_de_administracion_envia_tipo_de_entidad_y_ambito(): void
    {
        Http::fake([self::CRM.'/*' => Http::response('true')]);

        $this->postJson('/api/contacto', ['accion' => 'demo'] + $this->persona('tecnico@cabildo.test') + [
            'tipo' => 'administracion', 'entidad_nombre' => 'Cabildo de Prueba', 'cif' => 'P3500000X',
            'tipo_entidad' => 'cabildo', 'ambito' => 'insular', 'direccion' => 'Calle Ejemplo 3',
            'municipio' => 'Las Palmas de Gran Canaria', 'provincia' => 'Las Palmas',
        ])->assertCreated();

        Http::assertSent(fn (Request $request) => $request->url() === self::CRM.'/api/v1/LeadCapture/clave-admin'
            && $request['route'] === 'Administración'
            && $request['requestType'] === 'Demo'
            && $request['orgType'] === 'cabildo'
            && $request['territorialScope'] === 'insular'
            && ! isset($request['interests']));
    }

    public function test_el_origen_del_enlace_y_el_consentimiento_llegan_al_crm(): void
    {
        Http::fake([self::CRM.'/*' => Http::response('true')]);

        $this->postJson('/api/contacto', $this->empresa('origen@empresa.test') + [
            'consentimiento_marketing' => true,
            'origen' => [
                'utm_source' => 'linkedin', 'utm_medium' => 'cpc', 'utm_campaign' => 'lanzamiento-centros',
                'utm_content' => '', 'landing' => '/centros', 'referrer' => 'https://www.linkedin.com/',
            ],
        ])->assertCreated();

        $contacto = Contacto::sole();
        $this->assertSame(['utm_source' => 'linkedin', 'utm_medium' => 'cpc', 'utm_campaign' => 'lanzamiento-centros',
            'landing' => '/centros', 'referrer' => 'https://www.linkedin.com/'], $contacto->origen); // sin la clave vacía
        $this->assertNotNull($contacto->marketing_consent_at);
        $this->assertArrayNotHasKey('origen', $contacto->datos);
        $this->assertArrayNotHasKey('consentimiento_marketing', $contacto->datos);

        Http::assertSent(fn (Request $request) => $request['utmSource'] === 'linkedin'
            && $request['utmMedium'] === 'cpc'
            && $request['utmCampaign'] === 'lanzamiento-centros'
            && $request['landingPage'] === '/centros'
            && $request['referrerUrl'] === 'https://www.linkedin.com/'
            && $request['marketingConsent'] === true
            && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $request['marketingConsentAt']) === 1
            && ! isset($request['utmContent']));
    }

    public function test_sin_marcar_la_casilla_no_hay_consentimiento_aunque_venga_origen(): void
    {
        Http::fake([self::CRM.'/*' => Http::response('true')]);

        $this->postJson('/api/contacto', $this->empresa('sin@empresa.test') + [
            'consentimiento_marketing' => false, 'origen' => ['utm_source' => 'newsletter'],
        ])->assertCreated();

        $this->assertNull(Contacto::sole()->marketing_consent_at);
        Http::assertSent(fn (Request $request) => $request['marketingConsent'] === false
            && ! isset($request['marketingConsentAt']) && $request['utmSource'] === 'newsletter');
    }

    public function test_el_origen_solo_acepta_sus_claves_y_longitudes(): void
    {
        Http::fake();

        $this->postJson('/api/contacto', $this->empresa('largo@empresa.test') + [
            'origen' => ['utm_source' => str_repeat('a', 101)],
        ])->assertUnprocessable()->assertJsonValidationErrors('origen.utm_source');

        // Una clave que no está en la lista se descarta sin error
        $this->postJson('/api/contacto', $this->empresa('extra@empresa.test') + [
            'origen' => ['utm_source' => 'x', 'otra_cosa' => 'y'],
        ])->assertCreated();
        $this->assertSame(['utm_source' => 'x'], Contacto::sole()->origen);
    }

    public function test_con_la_integracion_apagada_no_se_envia_nada_pero_la_solicitud_se_guarda(): void
    {
        config(['crm.url' => '']);
        Http::fake();

        $this->postJson('/api/contacto', $this->empresa())->assertCreated();

        Http::assertNothingSent();
        $this->assertNull(Contacto::sole()->crm_enviado_at);
    }

    public function test_si_el_crm_rechaza_los_datos_queda_constancia_y_la_persona_no_lo_nota(): void
    {
        $rechazo = '{"messageTranslation":{"label":"validationFailure","data":{"field":"phoneNumber","type":"valid"}}}';
        Http::fake([self::CRM.'/*' => Http::response($rechazo, 400)]);

        $this->postJson('/api/contacto', $this->empresa())->assertCreated();

        Http::assertSentCount(1); // un 400 no se reintenta
        $contacto = Contacto::sole();
        $this->assertNull($contacto->crm_enviado_at);
        $this->assertStringContainsString('phoneNumber', $contacto->crm_error);
    }

    public function test_el_formulario_sencillo_sin_accion_no_va_al_crm(): void
    {
        Http::fake();

        $this->postJson('/api/contacto', [
            'nombre' => 'Ana López', 'email' => 'ana@empresa.test', 'tipo' => 'empresa', 'telefono' => '+34 612 345 678',
        ])->assertCreated();

        Http::assertNothingSent();
    }

    public function test_el_comando_reenvia_las_que_no_llegaron(): void
    {
        config(['crm.url' => '']);
        Http::fake([self::CRM.'/*' => Http::response('true')]);
        $this->postJson('/api/contacto', $this->empresa())->assertCreated();
        Http::assertNothingSent();

        config(['crm.url' => self::CRM]);
        $this->artisan('contactos:reenviar-crm')->assertSuccessful();

        Http::assertSentCount(1);
        $this->assertNotNull(Contacto::sole()->crm_enviado_at);
    }
}
