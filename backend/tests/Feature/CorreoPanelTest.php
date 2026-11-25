<?php

namespace Tests\Feature;

use App\Jobs\EnviarCorreoJob;
use App\Jobs\MarcarLeidoEnBuzonJob;
use App\Models\ActividadPanel;
use App\Models\Contacto;
use App\Models\CorreoEnvio;
use App\Models\CorreoMensaje;
use App\Models\CorreoSincronizacion;
use App\Models\User;
use App\Services\CorreoImapService;
use App\Services\CorreoSincronizacionService;
use App\Services\CorreoSmtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class CorreoPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config([
            'correo.buzones.info.password'            => 'secreto-de-prueba',
            'correo.buzones.info.guardar_en_enviados' => false,
        ]);
    }

    private function soloInbox(): void
    {
        config(['correo.buzones.info.carpetas' => ['INBOX' => 'recibidos']]);
    }

    private function usuario(string $rol): User
    {
        $u = User::factory()->create();
        $u->forceFill(['role' => $rol])->save();

        return $u;
    }

    private function datos(array $extra = []): array
    {
        return $extra + [
            'message_id' => '<abc@ejemplo.com>', 'in_reply_to' => null, 'referencias' => null,
            'de_email' => 'empresa@ejemplo.com', 'de_nombre' => 'Empresa Ejemplo',
            'para' => [['email' => 'info@dualab.es', 'nombre' => null]], 'cc' => [], 'responder_a' => [],
            'asunto' => 'Queremos colaborar', 'fecha' => now()->subDay(),
            'texto' => "Hola,\nqueremos colaborar.", 'html' => null, 'adjuntos' => [],
        ];
    }

    private function guardar(int $uid, array $extra = []): CorreoMensaje
    {
        return app(CorreoSincronizacionService::class)->guardar('info', 'INBOX', 1, $uid, $this->datos($extra));
    }

    // El correo se atiende en DuaLab Central: el panel ya no tiene rutas de correo y la API de
    // Central solo responde a peticiones firmadas
    public function test_el_correo_solo_se_ve_desde_central(): void
    {
        $correo = $this->guardar(1);
        $this->actingAs($this->usuario('admin'))->getJson('/api/admin/correo')->assertNotFound();
        $this->getJson('/api/integraciones/central/correo')->assertUnauthorized();
        $this->getJson("/api/integraciones/central/correo/{$correo->id}")->assertUnauthorized();

        $this->central()->getJson('/api/integraciones/central/correo')->assertOk()
            ->assertJsonPath('data.0.asunto', 'Queremos colaborar')
            ->assertJsonMissingPath('data.0.texto')
            ->assertJsonPath('resumen.retencion.meses', 4)
            ->assertJsonPath('resumen.buzon.configurado', true);
    }

    public function test_el_html_se_guarda_limpio(): void
    {
        $correo = $this->guardar(1, ['html' => '<p onclick="robar()">Hola <a href="javascript:alert(1)">aquí</a></p>'
            .'<script>alert(1)</script><form action="https://phishing.test"><input name="clave"></form>'
            .'<iframe src="https://malo.test"></iframe><img src="https://rastreo.test/p.gif"><a href="https://dualab.es">web</a>']);

        $this->assertStringNotContainsString('script', $correo->html);
        $this->assertStringNotContainsString('onclick', $correo->html);
        $this->assertStringNotContainsString('javascript:', $correo->html);
        $this->assertStringNotContainsString('<form', $correo->html);
        $this->assertStringNotContainsString('<input', $correo->html);
        $this->assertStringNotContainsString('<iframe', $correo->html);
        $this->assertStringContainsString('https://rastreo.test/p.gif', $correo->html); // la CSP del panel la bloquea
        $this->assertStringContainsString('rel="noreferrer noopener"', $correo->html);
        $this->assertSame("Hola, queremos colaborar.", $correo->extracto);
    }

    public function test_abrir_marca_leido_y_archivar_queda_en_la_actividad_sin_datos_del_remitente(): void
    {
        $admin = $this->usuario('admin');
        $correo = $this->guardar(1);

        $this->central()->getJson("/api/integraciones/central/correo/{$correo->id}")->assertOk()
            ->assertJsonPath('estado', 'leido')->assertJsonPath('texto', "Hola,\nqueremos colaborar.");

        $this->central()->patchJson("/api/integraciones/central/correo/{$correo->id}", ['estado' => 'archivado'])->assertOk();
        $this->central()->getJson('/api/integraciones/central/correo')->assertJsonCount(0, 'data');
        $this->central()->getJson('/api/integraciones/central/correo?estado=archivado')->assertJsonCount(1, 'data');

        $registro = ActividadPanel::where('seccion', 'correo')->sole();
        $this->assertStringNotContainsString('empresa@ejemplo.com', $registro->descripcion);
        $this->assertStringNotContainsString('colaborar', $registro->descripcion);

        $this->central()->patchJson("/api/integraciones/central/correo/{$correo->id}", ['estado' => 'borrado'])->assertUnprocessable();
    }

    // Central: la papelera saca el correo de la bandeja; borrar del todo solo desde la papelera
    public function test_papelera_del_correo(): void
    {
        $correo = $this->guardar(1);
        $otro = $this->guardar(2, ['message_id' => '<otro@ejemplo.com>']);

        $this->central()->deleteJson("/api/integraciones/central/correo/{$correo->id}")->assertUnprocessable();
        $this->central()->patchJson("/api/integraciones/central/correo/{$correo->id}", ['estado' => 'papelera'])->assertOk();
        $this->central()->patchJson("/api/integraciones/central/correo/{$otro->id}", ['estado' => 'papelera'])->assertOk();
        $this->central()->getJson('/api/integraciones/central/correo')->assertJsonCount(0, 'data')
            ->assertJsonPath('resumen.por_estado.papelera', 2);
        $this->central()->getJson('/api/integraciones/central/correo?estado=papelera')->assertJsonCount(2, 'data');

        $this->central()->deleteJson("/api/integraciones/central/correo/{$correo->id}")->assertNoContent();
        $this->assertModelMissing($correo);
        $this->central()->deleteJson('/api/integraciones/central/correo/papelera')->assertOk()->assertJsonPath('eliminados', 1);
        $this->assertModelMissing($otro);
        $this->assertSame(2, ActividadPanel::where('seccion', 'correo')->whereIn('accion', ['borrar', 'vaciar'])->count());
    }

    public function test_adjuntos_se_descargan_solo_desde_su_correo_y_siempre_como_descarga(): void
    {
        $admin = $this->usuario('admin');
        $con = $this->guardar(1, ['adjuntos' => [
            ['nombre' => '../../factura.html', 'tipo' => 'text/html', 'tamano' => 22, 'en_linea' => false, 'contenido' => '<script>x</script>'],
            ['nombre' => 'grande.zip', 'tipo' => 'application/zip', 'tamano' => 99_000_000, 'en_linea' => false, 'contenido' => null],
        ]]);
        $otro = $this->guardar(2);
        [$factura, $grande] = $con->adjuntos;

        $this->assertTrue($con->tiene_adjuntos);
        $this->assertSame('.._.._factura.html', $factura->nombre);

        $r = $this->central()->get("/api/integraciones/central/correo/{$con->id}/adjuntos/{$factura->id}")->assertOk();
        $this->assertStringStartsWith('attachment', $r->headers->get('Content-Disposition'));
        $this->assertSame('application/octet-stream', $r->headers->get('Content-Type'));
        $this->assertSame('nosniff', $r->headers->get('X-Content-Type-Options'));

        $this->central()->get("/api/integraciones/central/correo/{$otro->id}/adjuntos/{$factura->id}")->assertNotFound();
        $this->central()->get("/api/integraciones/central/correo/{$con->id}/adjuntos/{$grande->id}")->assertNotFound();
        $this->central()->getJson("/api/integraciones/central/correo/{$con->id}")
            ->assertJsonPath('adjuntos.0.disponible', true)->assertJsonPath('adjuntos.1.disponible', false)
            ->assertJsonMissingPath('adjuntos.0.ruta');
    }

    public function test_sincronizar_copia_lo_nuevo_y_quita_lo_borrado_en_hostinger(): void
    {
        $this->soloInbox();
        $this->guardar(5);
        $this->guardar(6);
        CorreoSincronizacion::create(['buzon' => 'info', 'carpeta' => 'INBOX', 'uidvalidity' => 1, 'ultimo_uid' => 6]);

        $imap = Mockery::mock(CorreoImapService::class);
        $imap->shouldReceive('conectar')->once();
        $imap->shouldReceive('desconectar')->once();
        $imap->shouldReceive('uidvalidity')->with('INBOX')->andReturn(1);
        $imap->shouldReceive('uidsDesde')->andReturn([6, 7, 8]);   // el 5 se borró desde el webmail
        $imap->shouldReceive('leer')->with('INBOX', 7, Mockery::any())->andReturn($this->datos(['asunto' => 'Nuevo 7']));
        $imap->shouldReceive('leer')->with('INBOX', 8, Mockery::any())->andThrow(new \RuntimeException('roto'));
        // El 6 ya se leyó en el webmail; el 7 no
        $imap->shouldReceive('leidos')->with('INBOX', Mockery::on(function ($u) { sort($u); return $u === [6, 7]; }))->andReturn([6 => true, 7 => false]);
        $this->app->instance(CorreoImapService::class, $imap);

        $this->artisan('correo:sincronizar')->assertSuccessful();

        $this->assertEqualsCanonicalizing([6, 7], CorreoMensaje::pluck('uid')->all());
        $this->assertSame(['6' => 'leido', '7' => 'nuevo'], CorreoMensaje::orderBy('uid')->pluck('estado', 'uid')->mapWithKeys(fn ($e, $u) => [(string) $u => $e])->all());
        $estado = CorreoSincronizacion::sole();
        $this->assertSame(7, (int) $estado->ultimo_uid);   // el 8 falla: se reintenta en la siguiente pasada
        $this->assertSame(1, (int) $estado->intentos_fallidos);
        $this->assertNotNull($estado->sincronizado_en);
        $this->assertNull($estado->ultimo_error);
    }

    public function test_un_correo_que_falla_tres_veces_se_omite_y_el_panel_avisa(): void
    {
        $this->soloInbox();
        CorreoSincronizacion::create(['buzon' => 'info', 'carpeta' => 'INBOX', 'uidvalidity' => 1, 'ultimo_uid' => 0]);

        $imap = Mockery::mock(CorreoImapService::class)->shouldIgnoreMissing();
        $imap->shouldReceive('uidvalidity')->andReturn(1);
        $imap->shouldReceive('uidsDesde')->andReturn([1, 2]);
        $imap->shouldReceive('leer')->with('INBOX', 1, Mockery::any())->times(3)->andThrow(new \RuntimeException('roto'));
        $imap->shouldReceive('leer')->with('INBOX', 2, Mockery::any())->once()->andReturn($this->datos());
        $this->app->instance(CorreoImapService::class, $imap);

        $this->artisan('correo:sincronizar');
        $this->artisan('correo:sincronizar');
        $this->assertSame(0, CorreoMensaje::count());      // el 2 espera a que se resuelva el 1
        $this->artisan('correo:sincronizar');

        $this->assertSame([2], CorreoMensaje::pluck('uid')->map(fn ($u) => (int) $u)->all());
        $this->central()->getJson('/api/integraciones/central/correo')->assertJsonPath('resumen.sincronizado.omitidos', 1);
    }

    public function test_asuntos_y_nombres_codificados_se_ven_legibles(): void
    {
        // Asunto en tres palabras MIME (como lo manda Brevo) y nombre entre comillas
        $correo = $this->guardar(1, [
            'asunto'    => '=?UTF-8?B?wqFUZSBkYW1vcyBsYQ==?= =?UTF-8?B?IGJpZW52ZW5pZGE=?= =?UTF-8?B?IGEgQnJldm8h?=',
            'de_nombre' => '"Instagram"',
        ]);

        $this->assertSame('¡Te damos la bienvenida a Brevo!', $correo->fresh()->asunto);
        $this->assertSame('Instagram', $correo->fresh()->de_nombre);
        $this->assertSame('Ya legible', CorreoImapService::cabecera('Ya legible'));
    }

    public function test_los_estilos_habituales_de_los_correos_no_rompen_el_guardado(): void
    {
        $correo = $this->guardar(1, ['html' => '<table style="border-collapse:collapse;max-width:600px"><tr><td style="border-radius:8px;display:block;'
            .'background-image:url(https://rastreo.test/f.png);padding:12px;color:#333;position:fixed">Hola</td></tr></table>']);

        $this->assertStringContainsString('padding:12px', $correo->html);
        $this->assertStringNotContainsString('url(', $correo->html);
        $this->assertStringNotContainsString('position', $correo->html);
    }

    public function test_si_cambia_el_uidvalidity_se_empieza_de_cero(): void
    {
        $this->soloInbox();
        $this->guardar(5);
        CorreoSincronizacion::create(['buzon' => 'info', 'carpeta' => 'INBOX', 'uidvalidity' => 1, 'ultimo_uid' => 5]);

        $imap = Mockery::mock(CorreoImapService::class)->shouldIgnoreMissing();
        $imap->shouldReceive('uidvalidity')->andReturn(2);
        $imap->shouldReceive('uidsDesde')->andReturn([1]);
        $imap->shouldReceive('leer')->with('INBOX', 1, Mockery::any())->andReturn($this->datos());
        $this->app->instance(CorreoImapService::class, $imap);

        $this->artisan('correo:sincronizar')->assertSuccessful();

        $this->assertSame([[2, 1]], CorreoMensaje::get(['uidvalidity', 'uid'])->map(fn ($m) => [(int) $m->uidvalidity, (int) $m->uid])->all());
    }

    public function test_si_no_conecta_se_guarda_un_aviso_generico(): void
    {
        $this->soloInbox();
        $imap = Mockery::mock(CorreoImapService::class)->shouldIgnoreMissing();
        $imap->shouldReceive('conectar')->andThrow(new \RuntimeException('AUTHENTICATIONFAILED info@dualab.es'));
        $this->app->instance(CorreoImapService::class, $imap);

        $this->artisan('correo:sincronizar')->assertSuccessful();

        $error = CorreoSincronizacion::sole()->ultimo_error;
        $this->assertStringNotContainsString('info@dualab.es', $error);
        $this->central()->getJson('/api/integraciones/central/correo')->assertJsonPath('resumen.sincronizado.error', $error);
    }

    public function test_sin_contrasena_no_se_conecta(): void
    {
        config(['correo.buzones.info.password' => null]);
        $imap = Mockery::mock(CorreoImapService::class);
        $imap->shouldNotReceive('conectar');
        $this->app->instance(CorreoImapService::class, $imap);

        $this->artisan('correo:sincronizar')->assertSuccessful();
    }

    public function test_se_borra_lo_de_mas_de_cuatro_meses_con_sus_adjuntos(): void
    {
        $viejo = $this->guardar(1, ['fecha' => now()->subMonths(5), 'adjuntos' => [
            ['nombre' => 'a.pdf', 'tipo' => 'application/pdf', 'tamano' => 3, 'en_linea' => false, 'contenido' => 'pdf'],
        ]]);
        $ruta = $viejo->adjuntos->first()->ruta;
        $this->guardar(2, ['fecha' => now()->subMonths(3)]);
        Storage::disk('local')->assertExists($ruta);

        $this->artisan('model:prune', ['--model' => [CorreoMensaje::class]])->assertSuccessful();

        $this->assertSame([2], CorreoMensaje::pluck('uid')->map(fn ($u) => (int) $u)->all());
        Storage::disk('local')->assertMissing($ruta);
    }

    // ── Envío ──────────────────────────────────────────────────────────────────

    /** SMTP simulado: guarda el Email que se habría enviado. */
    private function smtpFalso(): \stdClass
    {
        $capturado = new \stdClass;
        $smtp = Mockery::mock(CorreoSmtpService::class);
        $smtp->shouldReceive('enviar')->andReturnUsing(function ($buzon, Email $email) use ($capturado) {
            $capturado->email = $email;

            return "MIME-de-prueba";
        });
        $this->app->instance(CorreoSmtpService::class, $smtp);

        return $capturado;
    }

    public function test_enviar_un_correo_nuevo_con_adjunto(): void
    {
        $capturado = $this->smtpFalso();
        $admin = $this->usuario('admin');

        $this->central()->post('/api/integraciones/central/correo/enviar', [
            'tipo' => 'nuevo', 'para' => ['centro@ejemplo.com'], 'cco' => ['copia@ejemplo.com'],
            'asunto' => 'Propuesta de colaboración', 'texto' => "Hola,\nos escribimos desde DuaLab.",
            // Por la API de Central el archivo viaja de verdad (base64): contenido PDF real
            'adjuntos' => [UploadedFile::fake()->createWithContent('dossier.pdf', "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF")],
        ], ['Accept' => 'application/json'])->assertStatus(202);

        $envio = CorreoEnvio::sole();
        $this->assertSame('enviado', $envio->estado);
        $this->assertFalse($envio->copia_en_enviados);

        $email = $capturado->email;
        $this->assertSame('info@dualab.es', $email->getFrom()[0]->getAddress());
        $this->assertSame('centro@ejemplo.com', $email->getTo()[0]->getAddress());
        $this->assertSame('copia@ejemplo.com', $email->getBcc()[0]->getAddress());
        $this->assertStringContainsString('DuaLab · Formación profesional dual', $email->getTextBody());   // firma
        $this->assertSame('dossier.pdf', $email->getAttachments()[0]->getFilename());
        $this->assertSame(trim($envio->message_id, '<>'), $email->getHeaders()->get('Message-ID')->getId());

        $registro = ActividadPanel::where('seccion', 'correo')->sole();
        $this->assertStringNotContainsString('centro@ejemplo.com', $registro->descripcion);
    }

    public function test_responder_mantiene_el_hilo_y_marca_el_original_como_leido(): void
    {
        $capturado = $this->smtpFalso();
        $original = $this->guardar(1, ['message_id' => '<abc@ejemplo.com>', 'referencias' => '<inicio@ejemplo.com>']);

        $this->central()->postJson('/api/integraciones/central/correo/enviar', [
            'tipo' => 'responder', 'correo_id' => $original->id, 'para' => ['empresa@ejemplo.com'],
            'asunto' => 'Re: Queremos colaborar', 'texto' => "Gracias.\n\n> queremos colaborar.",
        ])->assertStatus(202);

        $cabeceras = $capturado->email->getHeaders();
        $this->assertSame('abc@ejemplo.com', $cabeceras->get('In-Reply-To')->getId());
        $this->assertSame(['inicio@ejemplo.com', 'abc@ejemplo.com'], $cabeceras->get('References')->getIds());
        $this->assertStringContainsString('<span style="color:#6b7280">&gt; queremos colaborar.</span>', $capturado->email->getHtmlBody());
        $this->assertSame('leido', $original->fresh()->estado);
    }

    public function test_reenviar_solo_con_adjuntos_del_correo_original(): void
    {
        $capturado = $this->smtpFalso();
        $admin = $this->usuario('admin');
        $original = $this->guardar(1, ['adjuntos' => [['nombre' => 'cv.pdf', 'tipo' => 'application/pdf', 'tamano' => 3, 'en_linea' => false, 'contenido' => 'pdf']]]);
        $ajeno = $this->guardar(2, ['adjuntos' => [['nombre' => 'otro.pdf', 'tipo' => 'application/pdf', 'tamano' => 3, 'en_linea' => false, 'contenido' => 'pdf']]]);
        $base = ['tipo' => 'reenviar', 'correo_id' => $original->id, 'para' => ['tutor@ejemplo.com'], 'asunto' => 'Fwd: CV', 'texto' => 'Te reenvío el CV.'];

        $this->central()->postJson('/api/integraciones/central/correo/enviar', $base + ['reenviar_adjuntos' => [$ajeno->adjuntos->first()->id]])
            ->assertUnprocessable()->assertJsonValidationErrors('reenviar_adjuntos.0');

        $this->central()->postJson('/api/integraciones/central/correo/enviar', $base + ['reenviar_adjuntos' => [$original->adjuntos->first()->id]])->assertStatus(202);
        $this->assertSame('cv.pdf', $capturado->email->getAttachments()[0]->getFilename());
        $this->assertNull($capturado->email->getHeaders()->get('In-Reply-To'));   // reenviar no responde al hilo
    }

    public function test_validacion_del_envio(): void
    {
        $this->smtpFalso();
        $admin = $this->usuario('admin');
        $base = ['tipo' => 'nuevo', 'para' => ['a@ejemplo.com'], 'asunto' => 'Hola', 'texto' => 'Texto'];

        $this->postJson('/api/integraciones/central/correo/enviar', $base)->assertUnauthorized();   // sin firma
        $this->central()->postJson('/api/integraciones/central/correo/enviar', ['para' => ['no-es-un-correo']] + $base)->assertJsonValidationErrors('para.0');
        $this->central()->postJson('/api/integraciones/central/correo/enviar', ['tipo' => 'responder'] + $base)->assertJsonValidationErrors('correo_id');
        $this->central()->post('/api/integraciones/central/correo/enviar', $base + ['adjuntos' => [UploadedFile::fake()->create('programa.exe', 5)]],
            ['Accept' => 'application/json'])->assertJsonValidationErrors('adjuntos.0');
        $this->central()->postJson('/api/integraciones/central/correo/enviar', ['para' => array_map(fn ($i) => "p{$i}@ejemplo.com", range(1, 21))] + $base)
            ->assertJsonValidationErrors('para');

        config(['correo.envio.max_por_dia' => 1]);
        $this->central()->postJson('/api/integraciones/central/correo/enviar', $base)->assertStatus(202);
        $this->central()->postJson('/api/integraciones/central/correo/enviar', $base)->assertJsonValidationErrors('para');
        $this->assertSame(1, CorreoEnvio::count());
    }

    public function test_si_el_smtp_falla_queda_en_error_y_se_puede_reintentar_o_descartar(): void
    {
        Queue::fake();
        $admin = $this->usuario('admin');
        $this->central()->postJson('/api/integraciones/central/correo/enviar', ['tipo' => 'nuevo', 'para' => ['a@ejemplo.com'], 'asunto' => 'Hola', 'texto' => 'Texto'])
            ->assertStatus(202);
        $envio = CorreoEnvio::sole();
        Queue::assertPushed(EnviarCorreoJob::class, fn ($j) => $j->envioId === $envio->id);

        // El job agota sus intentos
        (new EnviarCorreoJob($envio->id))->failed(new \RuntimeException('550 rechazado'));
        $this->assertSame('error', $envio->fresh()->estado);
        $this->central()->getJson('/api/integraciones/central/correo')->assertJsonPath('salida.0.estado', 'error');

        $this->central()->postJson("/api/integraciones/central/correo/envios/{$envio->id}/reintentar")->assertStatus(202);
        $this->assertSame('pendiente', $envio->fresh()->estado);
        $this->central()->deleteJson("/api/integraciones/central/correo/envios/{$envio->id}")->assertUnprocessable();   // pendiente: no se descarta

        $envio->forceFill(['estado' => 'error'])->save();
        $this->central()->deleteJson("/api/integraciones/central/correo/envios/{$envio->id}")->assertNoContent();
        $this->assertSame(0, CorreoEnvio::count());
    }

    public function test_envio_inmediato_y_si_el_smtp_falla_pasa_a_la_cola_sin_marcarse_en_error(): void
    {
        Queue::fake();
        $this->usuario('admin');
        $this->central()->postJson('/api/integraciones/central/correo/enviar', ['tipo' => 'nuevo', 'para' => ['a@ejemplo.com'], 'asunto' => 'Hola', 'texto' => 'Texto'])
            ->assertStatus(202);
        $envio = CorreoEnvio::sole();
        Queue::fake();   // olvida el de la petición (en tests va siempre a la cola)

        // El SMTP no responde: a la cola, con sus reintentos; el envío sigue pendiente
        $smtp = Mockery::mock(CorreoSmtpService::class);
        $smtp->shouldReceive('enviar')->once()->andThrow(new \RuntimeException('timeout'));
        $this->app->instance(CorreoSmtpService::class, $smtp);
        \App\Support\EnvioAlResponder::ejecutar(new EnviarCorreoJob($envio->id));
        Queue::assertPushed(EnviarCorreoJob::class, fn ($j) => $j->envioId === $envio->id);
        $this->assertSame('pendiente', $envio->fresh()->estado);

        // Responde: sale ya, sin pasar por la cola
        Queue::fake();
        $this->smtpFalso();
        \App\Support\EnvioAlResponder::ejecutar(new EnviarCorreoJob($envio->id));
        Queue::assertNothingPushed();
        $this->assertSame('enviado', $envio->fresh()->estado);
    }

    public function test_lo_enviado_se_guarda_en_enviados_y_no_se_reenvia(): void
    {
        config(['correo.buzones.info.guardar_en_enviados' => true]);
        $this->smtpFalso();
        $imap = Mockery::mock(CorreoImapService::class);
        $imap->shouldReceive('conectar')->once();
        $imap->shouldReceive('guardarEnviado')->once()->with('INBOX.Sent', 'MIME-de-prueba');
        $imap->shouldReceive('desconectar')->once();
        $this->app->instance(CorreoImapService::class, $imap);

        $this->central()->postJson('/api/integraciones/central/correo/enviar', ['tipo' => 'nuevo', 'para' => ['a@ejemplo.com'], 'asunto' => 'Hola', 'texto' => 'Texto'])
            ->assertStatus(202);
        $envio = CorreoEnvio::sole();
        $this->assertTrue($envio->copia_en_enviados);

        // Si la cola lo vuelve a lanzar, no sale dos veces
        app()->call([new EnviarCorreoJob($envio->id), 'handle']);
    }

    public function test_la_pestana_enviados_lista_la_carpeta_de_enviados_ya_leida(): void
    {
        app(CorreoSincronizacionService::class)->guardar('info', 'INBOX.Sent', 1, 1, $this->datos(['asunto' => 'Lo que enviamos']));
        $this->guardar(1);
        $admin = $this->usuario('admin');

        $this->central()->getJson('/api/integraciones/central/correo?carpeta=enviados')->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.asunto', 'Lo que enviamos')->assertJsonPath('data.0.estado', 'leido')
            ->assertJsonPath('data.0.carpeta', 'enviados')->assertJsonPath('data.0.para.0.email', 'info@dualab.es');
        $this->central()->getJson('/api/integraciones/central/correo')->assertJsonCount(1, 'data')
            ->assertJsonPath('resumen.por_estado.nuevo', 1);
    }

    // ── F4: leído en el buzón y Formularios ───────────────────────────────────

    public function test_leer_o_marcar_no_leido_en_el_panel_se_lleva_al_buzon(): void
    {
        Queue::fake();
        config(['correo.buzones.info.marcar_en_buzon' => true]);
        $admin = $this->usuario('admin');
        $correo = $this->guardar(1);
        $enviado = app(CorreoSincronizacionService::class)->guardar('info', 'INBOX.Sent', 1, 1, $this->datos());

        $this->central()->getJson("/api/integraciones/central/correo/{$correo->id}")->assertOk();
        Queue::assertPushed(MarcarLeidoEnBuzonJob::class, fn ($j) => $j->correoId === $correo->id && $j->leido === true);

        $this->central()->patchJson("/api/integraciones/central/correo/{$correo->id}", ['estado' => 'nuevo'])->assertOk();
        Queue::assertPushed(MarcarLeidoEnBuzonJob::class, fn ($j) => $j->correoId === $correo->id && $j->leido === false);

        // Archivar algo ya leído no cambia nada en el buzón; lo enviado no se marca
        $this->central()->patchJson("/api/integraciones/central/correo/{$correo->id}", ['estado' => 'leido']);
        $this->central()->patchJson("/api/integraciones/central/correo/{$correo->id}", ['estado' => 'archivado']);
        $this->central()->getJson("/api/integraciones/central/correo/{$enviado->id}");
        Queue::assertPushed(MarcarLeidoEnBuzonJob::class, 3);
    }

    public function test_en_local_no_se_marca_nada_en_el_buzon(): void
    {
        Queue::fake();
        config(['correo.buzones.info.marcar_en_buzon' => false]);
        $correo = $this->guardar(1);

        $this->central()->getJson("/api/integraciones/central/correo/{$correo->id}")->assertOk();
        Queue::assertNotPushed(MarcarLeidoEnBuzonJob::class);
    }

    public function test_el_job_marca_el_mensaje_y_no_toca_nada_si_cambio_el_uidvalidity(): void
    {
        $correo = $this->guardar(7);
        $imap = Mockery::mock(CorreoImapService::class);
        $imap->shouldReceive('conectar')->twice();
        $imap->shouldReceive('desconectar')->twice();
        $imap->shouldReceive('uidvalidity')->with('INBOX')->andReturn(1, 99);
        $imap->shouldReceive('marcarLeido')->once()->with('INBOX', 7, true);
        $this->app->instance(CorreoImapService::class, $imap);

        app()->call([new MarcarLeidoEnBuzonJob($correo->id, true), 'handle']);
        app()->call([new MarcarLeidoEnBuzonJob($correo->id, true), 'handle']);   // uidvalidity 99: no marca
    }

    public function test_responder_un_formulario_queda_en_su_ficha(): void
    {
        $this->smtpFalso();
        $admin = $this->usuario('admin');
        $contacto = Contacto::create(['nombre' => 'Ana', 'email' => 'ana@centro.test', 'tipo' => 'centro', 'accion' => 'demo']);

        $this->central()->postJson('/api/integraciones/central/correo/enviar', [
            'tipo' => 'nuevo', 'contacto_id' => $contacto->id, 'para' => ['ana@centro.test'],
            'asunto' => 'Tu solicitud de demo', 'texto' => 'Hola, Ana.',
        ])->assertStatus(202);

        $this->assertSame('leido', $contacto->fresh()->estado);
        $this->central()->getJson("/api/integraciones/central/formularios/{$contacto->id}")
            ->assertJsonPath('respuestas.0.asunto', 'Tu solicitud de demo')
            ->assertJsonPath('respuestas.0.estado', 'enviado')
            // Enviado desde Central: sin usuario del panel; quién fue queda en el registro de actividad
            ->assertJsonPath('respuestas.0.por', null);
        $this->assertSame('Central · Cynthia', ActividadPanel::latest('id')->value('usuario_nombre'));
    }

    public function test_el_correo_reconoce_a_quien_ya_escribio_por_un_formulario(): void
    {
        Contacto::create(['nombre' => 'Empresa', 'email' => 'Empresa@Ejemplo.com', 'tipo' => 'empresa', 'accion' => 'info']);
        $correo = $this->guardar(1);   // de empresa@ejemplo.com
        $this->guardar(2, ['de_email' => 'otro@ejemplo.com']);
        $admin = $this->usuario('admin');

        // Las claves llevan puntos (direcciones): se compara el mapa entero
        $this->assertSame(['empresa@ejemplo.com' => ['tipo' => 'empresa', 'tipo_nombre' => 'Empresa']],
            $this->central()->getJson('/api/integraciones/central/correo')->json('remitentes'));
        $this->central()->getJson("/api/integraciones/central/correo/{$correo->id}")
            ->assertJsonPath('contacto.tipo_nombre', 'Empresa');
    }
}
