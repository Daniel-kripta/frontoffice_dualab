<?php

namespace Tests\Feature;

use App\Http\Middleware\VerificarFirmaCentral;
use App\Models\ActividadPanel;
use App\Jobs\EnviarCorreoJob;
use App\Models\Contacto;
use App\Models\CorreoEnvio;
use App\Models\CorreoMensaje;
use App\Services\CorreoSincronizacionService;
use App\Services\CorreoSmtpService;
use Mockery;
use Symfony\Component\Mime\Email;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/** API firmada de la bandeja de formularios para DuaLab Central («Primer contacto»). */
class IntegracionCentralTest extends TestCase
{
    use RefreshDatabase;

    private const SECRETO = 'secreto-de-prueba-con-mas-de-32-caracteres';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.central.secreto' => self::SECRETO]);
        Storage::fake('local');
        config(['correo.buzones.info.password' => 'secreto-de-prueba', 'correo.buzones.info.guardar_en_enviados' => false]);
    }

    private function correo(int $uid = 1): CorreoMensaje
    {
        return app(CorreoSincronizacionService::class)->guardar('info', 'INBOX', 1, $uid, [
            'message_id' => '<abc@ejemplo.com>', 'in_reply_to' => null, 'referencias' => null,
            'de_email' => 'empresa@ejemplo.com', 'de_nombre' => 'Empresa Ejemplo',
            'para' => [['email' => 'info@dualab.es', 'nombre' => null]], 'cc' => [], 'responder_a' => [],
            'asunto' => 'Queremos colaborar', 'fecha' => now()->subDay(),
            'texto' => "Hola,\nqueremos colaborar.", 'html' => null, 'adjuntos' => [],
        ]);
    }

    private function contacto(array $extra = []): Contacto
    {
        $c = Contacto::create(array_merge([
            'nombre' => 'Ana', 'apellidos' => 'Pérez', 'email' => 'ana@empresa.test', 'tipo' => 'empresa',
            'accion' => 'info', 'datos' => ['empresa' => 'Empresa Test'], 'pagina_origen' => '/contacto',
        ], $extra));

        return $c->refresh();
    }

    /** Firma igual que el cliente de Central. */
    private function firmado(string $metodo, string $uri, array $cuerpo = [], ?int $fecha = null, string $secreto = self::SECRETO)
    {
        $fecha ??= time();
        $json = $cuerpo ? json_encode($cuerpo) : '';
        $req = Request::create($uri, $metodo, [], [], [], ['HTTP_X_DUALAB_USUARIO' => 'Cynthia'], $json);
        $firma = hash_hmac('sha256', VerificarFirmaCentral::cadena($req, (string) $fecha), $secreto);
        // call() con el cuerpo en crudo: lo mismo que firma el cliente (un GET va sin cuerpo)
        return $this->call($metodo, $uri, [], [], [], $this->transformHeadersToServerVars([
            'X-DuaLab-Fecha' => (string) $fecha, 'X-DuaLab-Firma' => $firma, 'X-DuaLab-Usuario' => 'Cynthia',
            'Accept' => 'application/json', 'Content-Type' => 'application/json',
        ]), $json);
    }

    public function test_lista_formularios_con_firma_valida(): void
    {
        $this->contacto();
        $this->firmado('GET', '/api/integraciones/central/formularios?estado=nuevo&per_page=10')
            ->assertOk()
            ->assertJsonPath('data.0.email', 'ana@empresa.test')
            ->assertJsonMissingPath('data.0.datos')
            ->assertJsonStructure(['resumen' => ['por_estado'], 'formularios', 'meta']);
    }

    public function test_sin_firma_firma_falsa_o_caducada_se_rechaza(): void
    {
        $this->contacto();
        $this->getJson('/api/integraciones/central/formularios')->assertStatus(401);
        $this->firmado('GET', '/api/integraciones/central/formularios', secreto: 'otro-secreto-cualquiera-de-mas-de-32-car')->assertStatus(401);
        $this->firmado('GET', '/api/integraciones/central/formularios', fecha: time() - 600)->assertStatus(401);
    }

    public function test_cambiar_la_query_invalida_la_firma(): void
    {
        $fecha = time();
        $req = Request::create('/api/integraciones/central/formularios?estado=nuevo', 'GET', [], [], [], ['HTTP_X_DUALAB_USUARIO' => 'Cynthia']);
        $firma = hash_hmac('sha256', VerificarFirmaCentral::cadena($req, (string) $fecha), self::SECRETO);
        $this->withHeaders(['X-DuaLab-Fecha' => (string) $fecha, 'X-DuaLab-Firma' => $firma, 'X-DuaLab-Usuario' => 'Cynthia'])
            ->getJson('/api/integraciones/central/formularios?estado=archivado')->assertStatus(401);
    }

    public function test_sin_secreto_configurado_no_responde(): void
    {
        config(['services.central.secreto' => '']);
        $this->firmado('GET', '/api/integraciones/central/formularios')->assertStatus(503);
    }

    public function test_ficha_marca_leido_y_trae_los_datos(): void
    {
        $c = $this->contacto();
        $this->firmado('GET', "/api/integraciones/central/formularios/{$c->id}")
                        ->assertOk()->assertJsonPath('datos.empresa', 'Empresa Test')->assertJsonPath('estado', 'leido');
    }

    public function test_cambiar_estado_registra_actividad_con_el_nombre_de_central(): void
    {
        $c = $this->contacto();
        $this->firmado('PATCH', "/api/integraciones/central/formularios/{$c->id}", ['estado' => 'archivado'])
            ->assertOk()->assertJsonPath('estado', 'archivado');
        $this->assertSame('Central · Cynthia', ActividadPanel::latest('id')->first()->usuario_nombre);

        $this->firmado('PATCH', "/api/integraciones/central/formularios/{$c->id}", ['estado' => 'borrado'])->assertStatus(422);
    }

    public function test_correo_lista_y_ficha_con_cuerpo(): void
    {
        $c = $this->correo();
        $this->firmado('GET', '/api/integraciones/central/correo')->assertOk()
            ->assertJsonPath('data.0.asunto', 'Queremos colaborar')->assertJsonMissingPath('data.0.texto')
            ->assertJsonStructure(['resumen' => ['por_estado'], 'salida']);
        $this->firmado('GET', "/api/integraciones/central/correo/{$c->id}")->assertOk()
            ->assertJsonPath('texto', "Hola,\nqueremos colaborar.")->assertJsonPath('estado', 'leido');
    }

    public function test_bandejas_aparte_por_asunto_o_remitente(): void
    {
        $guardar = fn (int $uid, string $de, string $asunto) => app(CorreoSincronizacionService::class)->guardar('info', 'INBOX', 1, $uid, [
            'message_id' => "<m{$uid}@ejemplo.com>", 'in_reply_to' => null, 'referencias' => null,
            'de_email' => $de, 'de_nombre' => null, 'para' => [], 'cc' => [], 'responder_a' => [],
            'asunto' => $asunto, 'fecha' => now()->subMinutes($uid), 'texto' => 'x', 'html' => null, 'adjuntos' => [],
        ]);
        $respuesta = $guardar(1, 'profe@centro.test', 'Re: Tus retos · Ref. SR-3');
        $sinRef    = $guardar(2, 'PROFE@centro.test', 'Una duda');
        $otro      = $guardar(3, 'empresa@ejemplo.com', 'Queremos colaborar');

        $ids = fn ($r) => collect($r->assertOk()->json('data'))->pluck('id')->sort()->values()->all();
        $q = http_build_query(['incluir_asunto' => 'Ref. SR-', 'incluir_de' => ['profe@centro.test']]);
        $this->assertSame([$respuesta->id, $sinRef->id], $ids($this->firmado('GET', "/api/integraciones/central/correo?{$q}")));

        $q = http_build_query(['excluir_asunto' => 'Ref. SR-', 'excluir_de' => ['profe@centro.test']]);
        $r = $this->firmado('GET', "/api/integraciones/central/correo?{$q}");
        $this->assertSame([$otro->id], $ids($r));
        // El contador de no leídos tampoco cuenta lo excluido
        $this->assertSame(1, $r->json('resumen.por_estado.nuevo'));
    }

    public function test_envio_con_html_maquetado_sale_limpio_y_sin_firma_añadida(): void
    {
        $capturado = new \stdClass;
        $smtp = Mockery::mock(CorreoSmtpService::class);
        $smtp->shouldReceive('enviar')->andReturnUsing(function ($buzon, Email $email) use ($capturado) {
            $capturado->email = $email;

            return 'MIME-de-prueba';
        });
        $this->app->instance(CorreoSmtpService::class, $smtp);

        $this->firmado('POST', '/api/integraciones/central/correo/enviar', [
            'tipo' => 'nuevo', 'para' => ['profe@centro.test'], 'asunto' => 'Tus retos · Ref. SR-3',
            'texto' => "Hola\n\nUn saludo, DuaLab",
            'html' => '<table style="background:#EDF4FA"><tr><td><a href="https://dualab.es/x" style="background:#509928">Ver</a>'
                . '<script>alert(1)</script><img src="x" onerror="alert(1)"></td></tr></table>',
        ])->assertStatus(202);

        $envio = CorreoEnvio::latest('id')->first();
        $this->assertStringContainsString('background:#509928', $envio->html);
        $this->assertStringNotContainsString('script', $envio->html);
        $this->assertStringNotContainsString('onerror', $envio->html);

        $this->assertSame($envio->html, $capturado->email->getHtmlBody());
        $this->assertSame("Hola\n\nUn saludo, DuaLab", $capturado->email->getTextBody());
    }

    public function test_responder_desde_central_encola_el_correo_con_adjunto(): void
    {
        Queue::fake();
        $c = $this->correo();
        $form = $this->contacto();
        $this->firmado('POST', '/api/integraciones/central/correo/enviar', [
            'tipo' => 'responder', 'correo_id' => $c->id, 'contacto_id' => $form->id,
            'para' => ['empresa@ejemplo.com'], 'asunto' => 'Re: Queremos colaborar', 'texto' => 'Gracias',
            'adjuntos_b64' => [['nombre' => 'nota.txt', 'contenido' => base64_encode('hola')]],
        ])->assertStatus(202);

        $envio = CorreoEnvio::with('adjuntos')->latest('id')->first();
        $this->assertNull($envio->user_id);
        $this->assertSame($form->id, $envio->contacto_id);
        $this->assertSame('<abc@ejemplo.com>', $envio->in_reply_to);
        $this->assertSame('nota.txt', $envio->adjuntos->first()->nombre);
        Queue::assertPushed(EnviarCorreoJob::class);
        $this->assertSame('Central · Cynthia', ActividadPanel::latest('id')->first()->usuario_nombre);
    }

    public function test_adjunto_no_permitido_se_rechaza(): void
    {
        Queue::fake();
        $this->firmado('POST', '/api/integraciones/central/correo/enviar', [
            'tipo' => 'nuevo', 'para' => ['a@ejemplo.com'], 'asunto' => 'Hola', 'texto' => 'x',
            'adjuntos_b64' => [['nombre' => 'virus.exe', 'contenido' => base64_encode("MZ\x90\x00")]],
        ])->assertStatus(422)->assertJsonValidationErrors('adjuntos.0');
        Queue::assertNothingPushed();
    }
    public function test_central_lee_un_envio_para_su_historial(): void
    {
        $envio = \App\Models\CorreoEnvio::create([
            'buzon' => 'info', 'tipo' => 'nuevo', 'para' => ['empresa@ejemplo.com'], 'cc' => [], 'cco' => [],
            'asunto' => 'Propuesta DuaLab', 'texto' => 'Hola, os mandamos la propuesta.', 'message_id' => '<x@dualab.es>',
        ]);

        $this->firmado('GET', "/api/integraciones/central/correo/envios/{$envio->id}")->assertOk()
            ->assertJsonPath('para', ['empresa@ejemplo.com'])
            ->assertJsonPath('asunto', 'Propuesta DuaLab')
            ->assertJsonPath('texto', 'Hola, os mandamos la propuesta.')
            ->assertJsonPath('adjuntos', []);

        $this->firmado('GET', '/api/integraciones/central/correo/envios/999')->assertNotFound();
        $this->getJson("/api/integraciones/central/correo/envios/{$envio->id}")->assertStatus(401);
    }
}
