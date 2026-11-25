<?php

namespace Tests\Feature;

use App\Jobs\EnviarFormularioAWebhook;
use App\Models\Ajuste;
use App\Models\Contacto;
use App\Models\Entrada;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GestionWebTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol): User
    {
        $u = User::factory()->create();
        $u->forceFill(['role' => $rol])->save();

        return $u;
    }

    public function test_solo_admin_y_editor_entran_al_panel(): void
    {
        $this->getJson('/api/admin/escritorio')->assertUnauthorized();
        $this->actingAs($this->usuario('alumno'))->getJson('/api/admin/escritorio')->assertForbidden();
        $this->actingAs($this->usuario('editor'))->getJson('/api/admin/escritorio')->assertOk();
        $this->actingAs($this->usuario('admin'))->getJson('/api/admin/escritorio')->assertOk();
    }

    public function test_editor_no_gestiona_idiomas_ni_integraciones(): void
    {
        $editor = $this->usuario('editor');
        $this->actingAs($editor)->getJson('/api/admin/idiomas')->assertOk();
        $this->actingAs($editor)->postJson('/api/admin/idiomas', ['codigo' => 'en'])->assertForbidden();
        $this->actingAs($editor)->getJson('/api/admin/ajustes/integraciones')->assertForbidden();
        $this->actingAs($editor)->putJson('/api/admin/ajustes/analitica', ['ajustes' => ['ga4_id' => 'G-ABC1234']])->assertForbidden();
        $this->actingAs($editor)->getJson('/api/admin/ajustes/seo')->assertOk();
    }

    public function test_verificacion_de_search_console_solo_la_pone_el_admin(): void
    {
        $this->actingAs($this->usuario('editor'))
            ->putJson('/api/admin/ajustes/seo', ['ajustes' => ['search_console' => 'codigoDeOtraCuenta']])
            ->assertUnprocessable();
        $this->actingAs($this->usuario('editor'))
            ->putJson('/api/admin/ajustes/seo', ['ajustes' => ['descripcion' => 'Formación dual']])
            ->assertOk();
        $this->actingAs($this->usuario('admin'))
            ->putJson('/api/admin/ajustes/seo', ['ajustes' => ['search_console' => 'abc-123_XY']])
            ->assertOk()->assertJson(['search_console' => 'abc-123_XY']);
    }

    public function test_editar_pagina_guarda_solo_lo_cambiado_y_la_web_lo_recibe(): void
    {
        $admin = $this->usuario('admin');

        $this->actingAs($admin)->putJson('/api/admin/paginas/centros', [
            'idioma'  => 'es',
            'valores' => ['hero' => [
                'titulo'          => 'Nuevo título',
                'destacado'       => 'simple y eficaz', // igual al original → no se guarda
                'boton_principal' => ['texto' => 'Únete', 'url' => '/contacto?tipo=centro'],
            ]],
            'seo' => ['meta_titulo' => 'Centros | DuaLab', 'meta_descripcion' => 'Para centros'],
        ])->assertOk()->assertJson(['valores' => ['hero.titulo' => 'Nuevo título']]);

        $this->assertDatabaseCount('contenidos_pagina', 2);

        $this->getJson('/api/web/paginas/centros')
            ->assertOk()
            ->assertJson(['valores' => [
                'hero.titulo'          => 'Nuevo título',
                'hero.boton_principal' => ['texto' => 'Únete'],
            ]])
            ->assertJsonPath('seo.meta_titulo', 'Centros | DuaLab');
    }

    public function test_pagina_rechaza_campos_fuera_del_esquema_y_enlaces_peligrosos(): void
    {
        $admin = $this->usuario('admin');

        $this->actingAs($admin)->putJson('/api/admin/paginas/centros', [
            'idioma' => 'es', 'valores' => ['hero' => ['inventado' => 'x']],
        ])->assertUnprocessable();

        $this->actingAs($admin)->putJson('/api/admin/paginas/centros', [
            'idioma' => 'es', 'valores' => ['hero' => ['boton_principal' => ['texto' => 'X', 'url' => 'javascript:alert(1)']]],
        ])->assertUnprocessable()->assertJsonValidationErrors('valores.hero.boton_principal.url');

        $this->actingAs($admin)->putJson('/api/admin/paginas/centros', [
            'idioma' => 'es', 'valores' => ['hero' => ['boton_principal' => ['texto' => 'X', 'url' => '//malo.com']]],
        ])->assertUnprocessable();
    }

    public function test_entrada_se_purifica_y_solo_se_publica_la_visible(): void
    {
        $admin = $this->usuario('admin');

        $id = $this->actingAs($admin)->postJson('/api/admin/entradas', [
            'idioma'    => 'es',
            'titulo'    => 'Hola mundo',
            'contenido' => '<p onclick="x()">Texto <strong>fuerte</strong></p><script>alert(1)</script><a href="javascript:alert(1)">mal</a>',
            'estado'    => 'publicada',
        ])->assertCreated()->assertJsonPath('slug', 'hola-mundo')->json('id');

        $contenido = Entrada::find($id)->contenido;
        $this->assertStringNotContainsString('<script', $contenido);
        $this->assertStringNotContainsString('onclick', $contenido);
        $this->assertStringNotContainsString('javascript:', $contenido);
        $this->assertStringContainsString('<strong>fuerte</strong>', $contenido);

        // Borrador y programada no salen en la web
        $this->actingAs($admin)->postJson('/api/admin/entradas', ['idioma' => 'es', 'titulo' => 'Borrador', 'estado' => 'borrador'])->assertCreated();
        $this->actingAs($admin)->postJson('/api/admin/entradas', [
            'idioma' => 'es', 'titulo' => 'Futura', 'estado' => 'publicada', 'publicada_en' => now()->addWeek()->toDateTimeString(),
        ])->assertCreated()->assertJsonPath('estado', 'programada');

        $this->getJson('/api/web/entradas')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', 'hola-mundo');
        $this->getJson('/api/web/entradas/borrador')->assertNotFound();
        $this->getJson('/api/web/entradas/hola-mundo')->assertOk()->assertJsonMissingPath('autor');
    }

    public function test_medios_rechaza_svg_y_acepta_imagen(): void
    {
        Storage::fake('public');
        $admin = $this->usuario('admin');

        $svg = UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        $this->actingAs($admin)->post('/api/admin/medios', ['archivo' => $svg], ['Accept' => 'application/json'])->assertUnprocessable();

        $png = UploadedFile::fake()->image('foto.png', 40, 30);
        $res = $this->actingAs($admin)->post('/api/admin/medios', ['archivo' => $png], ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonPath('tipo', 'imagen')->assertJsonPath('ancho', 40);

        Storage::disk('public')->assertExists(\App\Models\Medio::find($res->json('id'))->ruta);
    }

    public function test_bandeja_de_formularios_clasifica_marca_leido_y_exporta(): void
    {
        $admin = $this->usuario('editor');
        Contacto::create(['nombre' => 'Ana', 'email' => 'ana@x.com', 'tipo' => 'centro', 'accion' => 'demo', 'pagina_origen' => '/centros']);
        $rapido = Contacto::create(['nombre' => '=HYPERLINK("x")', 'email' => 'b@x.com', 'tipo' => 'empresa']);

        $this->central()->getJson('/api/integraciones/central/formularios?formulario=centro')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.formulario', 'centro');

        $this->central()->getJson("/api/integraciones/central/formularios/{$rapido->id}")
            ->assertOk()->assertJsonPath('formulario', 'rapido')->assertJsonPath('estado', 'leido');

        $csv = $this->central()->get('/api/integraciones/central/formularios/exportar')->assertOk()->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $csv); // fórmula neutralizada
    }

    public function test_formulario_publico_guarda_pagina_origen_y_encola_webhook(): void
    {
        Queue::fake();
        config(['services.app_dualab.webhook_url' => 'https://api.dualab.es/api/integraciones/frontoffice/formularios']);

        $this->postJson('/api/contacto', [
            'nombre' => 'Luis', 'email' => 'luis@x.com', 'tipo' => 'empresa', 'pagina_origen' => '/',
        ])->assertCreated();

        $this->assertDatabaseHas('contactos', ['email' => 'luis@x.com', 'pagina_origen' => '/', 'estado' => 'nuevo']);
        Queue::assertPushed(EnviarFormularioAWebhook::class);
    }

    public function test_formulario_avisa_a_la_app_y_al_crm_por_separado(): void
    {
        Queue::fake();
        // La app, desde el .env del servidor; el CRM, desde el panel
        config(['services.app_dualab.webhook_url' => 'https://api.dualab.es/api/integraciones/frontoffice/formularios',
            'services.app_dualab.webhook_secreto' => 'clave-compartida-con-la-app']);
        Ajuste::guardar('integraciones.crm_url', 'https://hooks.crm.ejemplo.com/x');
        Ajuste::guardar('integraciones.crm_activo', false);

        $this->postJson('/api/contacto', ['nombre' => 'Luis', 'email' => 'luis@x.com', 'tipo' => 'empresa', 'pagina_origen' => '/'])->assertCreated();
        Queue::assertPushed(EnviarFormularioAWebhook::class, 1);
        Queue::assertPushed(EnviarFormularioAWebhook::class, fn ($j) => $j->destino === 'webhook');

        // Con el CRM activo, dos avisos independientes (cada uno con sus reintentos)
        Ajuste::guardar('integraciones.crm_activo', true);
        $this->postJson('/api/contacto', ['nombre' => 'Eva', 'email' => 'eva@x.com', 'tipo' => 'centro', 'pagina_origen' => '/'])->assertCreated();
        Queue::assertPushed(EnviarFormularioAWebhook::class, fn ($j) => $j->destino === 'crm');
        Queue::assertPushed(EnviarFormularioAWebhook::class, 3);
    }

    public function test_probar_conexion_va_marcado_como_prueba_al_destino_elegido(): void
    {
        \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response(['recibido' => true], 200)]);
        $admin = $this->usuario('admin');
        Ajuste::guardar('integraciones.crm_url', 'https://hooks.crm.ejemplo.com/x');
        Ajuste::guardar('integraciones.crm_secreto', 'clave-del-crm-de-prueba');

        $this->actingAs($admin)->postJson('/api/admin/ajustes/integraciones/probar-webhook', ['destino' => 'crm'])->assertOk()->assertJsonPath('ok', true);
        \Illuminate\Support\Facades\Http::assertSent(fn ($r) => $r->url() === 'https://hooks.crm.ejemplo.com/x' && $r['prueba'] === true
            && $r->header('X-DuaLab-Firma')[0] === hash_hmac('sha256', $r->body(), 'clave-del-crm-de-prueba'));

        // Sin la app configurada en el servidor, no se prueba; el panel lo dice
        $this->actingAs($admin)->postJson('/api/admin/ajustes/integraciones/probar-webhook')->assertStatus(422)
            ->assertJsonPath('message', 'El aviso a la app DuaLab no está configurado en el servidor.');
        $this->actingAs($admin)->getJson('/api/admin/ajustes/integraciones')->assertJsonPath('app_dualab.conectada', false);

        // La dirección de la app ya no se cambia desde el panel
        $this->actingAs($admin)->putJson('/api/admin/ajustes/integraciones', ['ajustes' => ['webhook_url' => 'https://otra.web/x']])->assertUnprocessable();

        // Lo que se guardó en el panel antes de pasar al .env sigue funcionando hasta que el .env lo tenga
        Ajuste::guardar('integraciones.webhook_url', 'https://api.dualab.es/api/integraciones/frontoffice/formularios');
        Ajuste::guardar('integraciones.webhook_activo', true);
        $this->actingAs($admin)->getJson('/api/admin/ajustes/integraciones')
            ->assertJsonPath('app_dualab', ['conectada' => true, 'servidor' => 'api.dualab.es']);
        $this->actingAs($admin)->postJson('/api/admin/ajustes/integraciones/probar-webhook')->assertOk();
        $this->actingAs($admin)->postJson('/api/admin/ajustes/integraciones/probar-webhook', ['destino' => 'otro'])->assertUnprocessable();
        $this->actingAs($this->usuario('editor'))->postJson('/api/admin/ajustes/integraciones/probar-webhook')->assertForbidden();
    }

    public function test_secretos_cifrados_y_nunca_devueltos(): void
    {
        $admin = $this->usuario('admin');

        $this->actingAs($admin)->putJson('/api/admin/ajustes/integraciones', [
            'ajustes' => ['brevo_api_key' => 'xkeysib-secreta-123'],
        ])->assertOk()->assertJsonPath('brevo_api_key.configurado', true)
          ->assertDontSee('xkeysib-secreta-123');

        $this->assertDatabaseMissing('ajustes', ['valor' => json_encode('xkeysib-secreta-123')]);
        $this->assertSame('xkeysib-secreta-123', Ajuste::leer('integraciones.brevo_api_key'));
        $this->getJson('/api/web/configuracion')->assertOk()->assertDontSee('brevo');
    }

    public function test_sitemap_incluye_paginas_y_entradas_visibles(): void
    {
        $this->actingAs($this->usuario('admin'))->postJson('/api/admin/entradas', [
            'idioma' => 'es', 'titulo' => 'Noticia uno', 'estado' => 'publicada',
        ]);

        $this->get('/api/web/sitemap.xml')->assertOk()
            ->assertSee('/centros', false)
            ->assertSee('/noticias/noticia-uno', false)
            ->assertDontSee(':id', false);
    }

    public function test_galeria_y_apariencia_solo_aceptan_opciones_permitidas(): void
    {
        $admin = $this->usuario('admin');
        $foto  = \Illuminate\Support\Facades\Storage::disk('public')->url('medios/2026/11/a.jpg');

        $this->actingAs($admin)->putJson('/api/admin/paginas/inicio', [
            'idioma' => 'es', 'valores' => ['galeria' => ['fotos' => [$foto]]],
        ])->assertOk()->assertJson(['valores' => ['galeria.fotos' => [$foto]]]);

        $this->actingAs($admin)->putJson('/api/admin/paginas/inicio', [
            'idioma' => 'es', 'valores' => ['galeria' => ['fotos' => ['javascript:alert(1)']]],
        ])->assertUnprocessable();

        $this->actingAs($admin)->putJson('/api/admin/ajustes/apariencia', ['ajustes' => ['paleta' => 'teja', 'tamano_texto' => 'grande']])
            ->assertOk()->assertJsonPath('paleta', 'teja');
        $this->actingAs($admin)->putJson('/api/admin/ajustes/apariencia', ['ajustes' => ['paleta' => '#ff0000']])->assertUnprocessable();
        $this->actingAs($admin)->putJson('/api/admin/ajustes/apariencia', ['ajustes' => ['css' => 'body{}']])->assertUnprocessable();
        $this->actingAs($this->usuario('editor'))->getJson('/api/admin/ajustes/apariencia')->assertForbidden();
        $this->getJson('/api/web/configuracion')->assertJson(['ajustes' => ['apariencia.paleta' => 'teja']]);
    }

    public function test_fotos_grandes_se_reducen_y_hay_cuota(): void
    {
        Storage::fake('public');
        $admin = $this->usuario('admin');

        $this->actingAs($admin)->post('/api/admin/medios', ['archivo' => UploadedFile::fake()->image('grande.jpg', 4000, 3000)], ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonPath('ancho', 2400)->assertJsonPath('alto', 1800);

        config(['cms.medios.cuota_mb' => 0]);
        $this->actingAs($admin)->post('/api/admin/medios', ['archivo' => UploadedFile::fake()->image('otra.png', 10, 10)], ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors('archivo');
        $this->actingAs($admin)->getJson('/api/admin/medios')->assertJsonStructure(['uso' => ['usado', 'cuota']]);
    }

    public function test_flujo_de_aprobacion_de_entradas(): void
    {
        $editor = $this->usuario('editor');
        $admin  = $this->usuario('admin');

        // El editor no puede publicar: guarda borrador y lo envía a revisión
        $this->actingAs($editor)->postJson('/api/admin/entradas', ['idioma' => 'es', 'titulo' => 'Directa', 'estado' => 'publicada'])
            ->assertUnprocessable()->assertJsonValidationErrors('estado');
        $id = $this->actingAs($editor)->postJson('/api/admin/entradas', ['idioma' => 'es', 'titulo' => 'Mi noticia', 'estado' => 'borrador'])
            ->assertCreated()->assertJsonPath('permisos.aprobar', false)->json('id');
        $this->actingAs($editor)->putJson("/api/admin/entradas/$id", ['estado' => 'pendiente'])->assertOk()->assertJsonPath('estado', 'pendiente');
        $this->assertNotNull(Entrada::find($id)->enviada_revision_en);
        $this->getJson('/api/web/entradas/mi-noticia')->assertNotFound();

        // Solo el admin aprueba o devuelve
        $this->actingAs($editor)->postJson("/api/admin/entradas/$id/aprobar")->assertForbidden();
        $this->actingAs($admin)->postJson("/api/admin/entradas/$id/devolver", ['nota' => 'Añade una foto'])
            ->assertOk()->assertJsonPath('estado', 'borrador')->assertJsonPath('nota_revision', 'Añade una foto');
        $this->actingAs($editor)->putJson("/api/admin/entradas/$id", ['estado' => 'pendiente'])->assertJsonPath('nota_revision', null);
        $this->actingAs($admin)->postJson("/api/admin/entradas/$id/aprobar")->assertOk()->assertJsonPath('estado', 'publicada');
        $this->assertSame($admin->id, Entrada::find($id)->aprobada_por);
        $this->getJson('/api/web/entradas/mi-noticia')->assertOk();

        // Publicada: el editor ya no la toca; el admin puede retirarla de la web
        $this->actingAs($editor)->putJson("/api/admin/entradas/$id", ['titulo' => 'Cambio'])->assertForbidden();
        $this->actingAs($editor)->deleteJson("/api/admin/entradas/$id")->assertForbidden();
        $this->actingAs($admin)->postJson("/api/admin/entradas/$id/retirar")->assertOk()->assertJsonPath('estado', 'borrador');
        $this->getJson('/api/web/entradas/mi-noticia')->assertNotFound();
    }

    public function test_medios_van_a_cloudinary_si_esta_configurado(): void
    {
        config(['services.cloudinary' => ['cloud_name' => 'demo', 'api_key' => 'k', 'api_secret' => 's', 'folder' => 'dualab/web']]);
        \Illuminate\Support\Facades\Http::fake([
            'api.cloudinary.com/v1_1/demo/image/upload'  => \Illuminate\Support\Facades\Http::response([
                'secure_url' => 'https://res.cloudinary.com/demo/image/upload/v1/dualab/web/a.jpg',
                'public_id' => 'dualab/web/a', 'bytes' => 1234, 'width' => 40, 'height' => 30,
            ]),
            'api.cloudinary.com/v1_1/demo/image/destroy' => \Illuminate\Support\Facades\Http::response(['result' => 'ok']),
        ]);
        Storage::fake('public');
        $admin = $this->usuario('admin');

        $id = $this->actingAs($admin)->post('/api/admin/medios', ['archivo' => UploadedFile::fake()->image('a.jpg', 40, 30)], ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonPath('url', 'https://res.cloudinary.com/demo/image/upload/v1/dualab/web/a.jpg')->json('id');
        $this->assertSame([], Storage::disk('public')->allFiles()); // nada en el servidor

        $this->actingAs($admin)->deleteJson("/api/admin/medios/$id")->assertOk();
        \Illuminate\Support\Facades\Http::assertSent(fn ($r) => str_ends_with($r->url(), '/image/destroy') && $r['public_id'] === 'dualab/web/a');
    }

    public function test_perfil_cambia_nombre_y_contrasena_con_la_actual(): void
    {
        $editor = $this->usuario('editor');

        $this->actingAs($editor)->patchJson('/api/admin/perfil', ['name' => 'Nuevo nombre'])
            ->assertOk()->assertJsonPath('password_changed', false)->assertJsonPath('user.name', 'Nuevo nombre');
        $this->actingAs($editor)->patchJson('/api/admin/perfil', [
            'name' => 'Nuevo nombre', 'password' => 'Nueva-Clave1!', 'password_confirmation' => 'Nueva-Clave1!',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');
        $this->actingAs($editor)->patchJson('/api/admin/perfil', [
            'name' => 'Nuevo nombre', 'password' => 'Nueva-Clave1!', 'password_confirmation' => 'Nueva-Clave1!', 'current_password' => 'password',
        ])->assertOk()->assertJsonPath('password_changed', true);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('Nueva-Clave1!', $editor->fresh()->password));
    }

    public function test_paleta_personalizada_exige_colores_legibles(): void
    {
        $admin = $this->usuario('admin');
        $url   = '/api/admin/ajustes/apariencia';

        $this->actingAs($admin)->putJson($url, ['ajustes' => ['paleta' => 'personalizada']])
            ->assertUnprocessable()->assertJsonValidationErrors(['ajustes.color_principal', 'ajustes.color_noche']);
        $this->actingAs($admin)->putJson($url, ['ajustes' => ['paleta' => 'personalizada', 'color_principal' => '#FFD400', 'color_noche' => '#17283E']])
            ->assertUnprocessable()->assertJsonValidationErrors('ajustes.color_principal'); // amarillo: ilegible con texto blanco
        $this->actingAs($admin)->putJson($url, ['ajustes' => ['paleta' => 'personalizada', 'color_principal' => 'red', 'color_noche' => '#17283E']])
            ->assertUnprocessable();
        $this->actingAs($admin)->putJson($url, ['ajustes' => ['paleta' => 'personalizada', 'color_principal' => '#7A1F5C', 'color_noche' => '#1E1B2E', 'fuente_titulos' => 'playfair']])
            ->assertOk()->assertJsonPath('color_principal', '#7A1F5C');
        // Títulos con color propio: legible sobre blanco o se rechaza
        $this->actingAs($admin)->putJson($url, ['ajustes' => ['paleta' => 'personalizada', 'color_principal' => '#7A1F5C', 'color_noche' => '#1E1B2E', 'color_titulos' => '#3D7A1E']])
            ->assertOk()->assertJsonPath('color_titulos', '#3D7A1E');
        $this->actingAs($admin)->putJson($url, ['ajustes' => ['paleta' => 'personalizada', 'color_principal' => '#7A1F5C', 'color_noche' => '#1E1B2E', 'color_titulos' => '#9BE15D']])
            ->assertUnprocessable()->assertJsonValidationErrors('ajustes.color_titulos');
    }

    public function test_lista_de_fuentes_coincide_con_el_catalogo_del_frontend(): void
    {
        $ruta = base_path('../frontend/src/data/fuentes.json');
        if (! is_file($ruta)) {
            $this->markTestSkipped('Sin el frontend al lado (p. ej. en el servidor).');
        }
        $catalogo = collect(json_decode(file_get_contents($ruta), true)['fuentes']);

        foreach (['titulos', 'texto'] as $uso) {
            $this->assertSame(
                $catalogo->filter(fn ($f) => in_array($uso, $f['usos'], true))->pluck('id')->values()->all(),
                config("cms.fuentes.$uso"),
                "config/cms.php → fuentes.$uso no coincide con fuentes.json",
            );
        }

        $admin = $this->usuario('admin');
        $this->actingAs($admin)->putJson('/api/admin/ajustes/apariencia', ['ajustes' => ['fuente_titulos' => 'unbounded', 'fuente_texto' => 'manrope']])->assertOk();
        // De cartel: solo para títulos
        $this->actingAs($admin)->putJson('/api/admin/ajustes/apariencia', ['ajustes' => ['fuente_texto' => 'unbounded']])->assertUnprocessable();
        $this->actingAs($admin)->putJson('/api/admin/ajustes/apariencia', ['ajustes' => ['fuente_titulos' => 'comic-sans']])->assertUnprocessable();
    }

    public function test_la_web_recibe_las_fotos_de_cloudinary_optimizadas(): void
    {
        $original = 'https://res.cloudinary.com/demo/image/upload/v1/dualab/web/a.jpg';
        $this->assertSame('https://res.cloudinary.com/demo/image/upload/f_auto,q_auto,c_limit,w_800/v1/dualab/web/a.jpg',
            \App\Services\CloudinaryService::optimizar($original, 800));
        // Ya optimizada, de vídeo o de otro sitio: no cambia
        $this->assertSame('https://res.cloudinary.com/demo/image/upload/f_auto,q_auto,c_limit,w_800/v1/a.jpg',
            \App\Services\CloudinaryService::optimizar('https://res.cloudinary.com/demo/image/upload/f_auto,q_auto,c_limit,w_800/v1/a.jpg'));
        $this->assertSame('https://res.cloudinary.com/demo/video/upload/v1/a.mp4', \App\Services\CloudinaryService::optimizar('https://res.cloudinary.com/demo/video/upload/v1/a.mp4'));
        $this->assertSame('/storage/medios/a.jpg', \App\Services\CloudinaryService::optimizar('/storage/medios/a.jpg'));
        $this->assertStringContainsString('upload/f_auto,q_auto,c_limit,w_1200/v1/dualab/web/a.jpg',
            \App\Services\CloudinaryService::optimizarHtml('<p><img src="'.$original.'" alt=""></p>'));
    }

    public function test_almacenamiento_muestra_conexion_y_consumo_sin_claves_solo_al_admin(): void
    {
        config(['services.cloudinary' => ['cloud_name' => 'demo', 'api_key' => 'clave-publica', 'api_secret' => 'secreto-xyz', 'folder' => 'dualab/web']]);
        \Illuminate\Support\Facades\Http::fake([
            'api.cloudinary.com/v1_1/demo/usage' => \Illuminate\Support\Facades\Http::response([
                'plan' => 'Free', 'credits' => ['usage' => 0.18, 'limit' => 25], 'storage' => ['usage' => 1000],
                'bandwidth' => ['usage' => 500], 'transformations' => ['usage' => 4], 'resources' => 180,
            ]),
        ]);

        $this->actingAs($this->usuario('editor'))->getJson('/api/admin/medios/almacenamiento')->assertForbidden();

        $r = $this->actingAs($this->usuario('admin'))->getJson('/api/admin/medios/almacenamiento')->assertOk()
            ->assertJson(['conexion' => ['configurado' => true, 'ok' => true, 'cloud_name' => 'demo', 'carpeta' => 'dualab/web'],
                'consumo' => ['plan' => 'Free', 'creditos_usados' => 0.18, 'creditos_limite' => 25, 'archivos' => 180]]);
        $this->assertStringNotContainsString('secreto-xyz', $r->getContent());
        $this->assertStringNotContainsString('clave-publica', $r->getContent());
    }

    public function test_web_publica_sale_igual_desde_la_cache_en_disco(): void
    {
        // Como en producción: caché en archivo, que no deserializa objetos (cache.serializable_classes = false)
        $carpeta = storage_path('framework/cache/test-'.uniqid());
        config(['cache.default' => 'file', 'cache.stores.file.path' => $carpeta]);

        try {
            foreach ([1, 2] as $vez) { // la segunda vez sale de la caché
                $config = $this->getJson('/api/web/configuracion')->assertOk()->assertDontSee('__PHP_Incomplete_Class');
                $this->assertIsList($config->json('idiomas'));
                $this->assertArrayHasKey('apariencia.paleta', $config->json('ajustes'));

                $pagina = $this->getJson('/api/web/paginas/centros')->assertOk()->assertDontSee('__PHP_Incomplete_Class');
                $this->assertStringContainsString('"valores":{}', $pagina->getContent(), "lectura $vez");
            }
        } finally {
            File::deleteDirectory($carpeta);
        }
    }
}
