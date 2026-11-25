<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/** La web pública servida con su texto ya escrito, para Google y las IA (HtmlPublicoService). */
class HtmlPublicoTest extends TestCase
{
    use RefreshDatabase;

    private string $indice;

    protected function setUp(): void
    {
        parent::setUp();
        // Como el index.html de la build de Vite
        $this->indice = tempnam(sys_get_temp_dir(), 'web').'.html';
        File::put($this->indice, '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8" /><title>DuaLab</title>'
            .'<script type="module" crossorigin src="/home/assets/index-abc.js"></script></head>'
            .'<body><div id="app"></div></body></html>');
        config(['cms.web_index' => $this->indice, 'cms.url_publica' => 'https://dualab.es']);
    }

    protected function tearDown(): void
    {
        File::delete($this->indice);
        parent::tearDown();
    }

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->forceFill(['role' => 'admin'])->save();

        return $u;
    }

    public function test_la_portada_lleva_su_texto_y_sus_etiquetas(): void
    {
        $r = $this->get('/')->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeaderMissing('Set-Cookie');

        $html = $r->getContent();
        $this->assertStringContainsString('<title>Conecta talento con retos reales | DuaLab</title>', $html);
        $this->assertStringContainsString('<link rel="canonical" href="https://dualab.es/" />', $html);
        $this->assertStringContainsString('<h1 class="text-3xl font-bold">Conecta talento con retos reales</h1>', $html);
        $this->assertStringContainsString('DuaLab es la solución definitiva', $html);
        $this->assertStringContainsString('<a href="/empresas-asociadas">Empresas</a>', $html);
        $this->assertStringContainsString('"@type":"WebSite"', $html);
        // La SPA sigue cargando igual
        $this->assertStringContainsString('src="/home/assets/index-abc.js"', $html);
        $this->assertSame(1, substr_count($html, '<title>'));
    }

    public function test_lo_editado_en_el_panel_sale_al_momento_y_escapado(): void
    {
        $this->actingAs($this->admin())->putJson('/api/admin/paginas/centros', [
            'idioma'  => 'es',
            'valores' => ['hero' => ['titulo' => 'Centros <script>alert(1)</script>']],
            'seo'     => ['meta_titulo' => 'FP dual para centros', 'meta_descripcion' => 'Gestiona la FP dual de tu centro.'],
        ])->assertOk();

        $html = $this->get('/centros')->assertOk()->getContent();
        $this->assertStringContainsString('<title>FP dual para centros</title>', $html);
        $this->assertStringContainsString('<meta name="description" content="Gestiona la FP dual de tu centro." />', $html);
        $this->assertStringContainsString('Centros &lt;script&gt;alert(1)&lt;/script&gt; simple y eficaz', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
    }

    public function test_pagina_despublicada_lleva_a_la_portada(): void
    {
        $this->actingAs($this->admin())->putJson('/api/admin/paginas/entidades', ['idioma' => 'es', 'publicada' => false])->assertOk();

        $this->get('/entidades')->assertRedirect('/');
    }

    public function test_noticia_con_su_texto_y_datos_de_articulo(): void
    {
        $this->actingAs($this->admin())->putJson('/api/admin/ajustes/seo', ['ajustes' => ['org_nombre' => 'DuaLab']])->assertOk();
        $this->actingAs($this->admin())->postJson('/api/admin/entradas', [
            'idioma' => 'es', 'titulo' => 'DuaLab llega a Gran Canaria', 'estado' => 'publicada',
            'extracto' => 'Diez centros se suman.', 'contenido' => '<p>Texto de la noticia</p><script>x()</script>',
        ])->assertCreated();

        $html = $this->get('/noticias/dualab-llega-a-gran-canaria')->assertOk()->getContent();
        $this->assertStringContainsString('<h1 class="text-3xl font-bold">DuaLab llega a Gran Canaria</h1>', $html);
        $this->assertStringContainsString('<p>Texto de la noticia</p>', $html);
        $this->assertStringNotContainsString('x()', $html);
        $this->assertStringContainsString('"@type":"NewsArticle"', $html);
        $this->assertStringContainsString('id="ld-organizacion"', $html);
        $this->assertStringContainsString('<meta name="description" content="Diez centros se suman." />', $html);

        $this->assertStringContainsString('DuaLab llega a Gran Canaria', $this->get('/noticias')->getContent());

        $this->get('/noticias/no-existe')->assertNotFound()->assertSee('noindex, nofollow', false);
    }

    public function test_json_ld_no_se_puede_cerrar_desde_un_texto(): void
    {
        $this->actingAs($this->admin())->putJson('/api/admin/ajustes/seo', ['ajustes' => [
            'org_nombre' => 'DuaLab</script><script>alert(1)</script>',
        ]])->assertOk();

        $html = $this->get('/')->getContent();
        $this->assertStringNotContainsString('</script><script>alert(1)', $html);
        $this->assertStringContainsString('</script>', $html);
    }

    public function test_las_tarjetas_salen_como_apartados(): void
    {
        config(['cms_paginas.centros.secciones.ventajas' => ['titulo' => 'Ventajas', 'campos' => [
            'titulo'        => ['tipo' => 'texto', 'etiqueta' => 'Título', 'max' => 120, 'defecto' => 'Por qué DuaLab'],
            'item_1_titulo' => ['tipo' => 'texto', 'etiqueta' => 'Ventaja 1', 'max' => 80, 'defecto' => 'Seguimiento'],
            'item_1_texto'  => ['tipo' => 'texto', 'etiqueta' => 'Ventaja 1: texto', 'max' => 300, 'defecto' => 'Todo el proceso en un sitio.'],
            'item_2_titulo' => ['tipo' => 'texto', 'etiqueta' => 'Ventaja 2', 'max' => 80, 'defecto' => 'Evaluación'],
        ]]]);

        $html = $this->get('/centros')->assertOk()->getContent();
        $this->assertStringContainsString('<h2 class="text-2xl font-bold">Por qué DuaLab</h2>', $html);
        $this->assertMatchesRegularExpression('#<h3 class="font-bold">Seguimiento</h3>\s*<p>Todo el proceso en un sitio.</p>#', $html);
        $this->assertStringContainsString('<h3 class="font-bold">Evaluación</h3>', $html);
    }

    public function test_preguntas_frecuentes_en_datos_estructurados_y_llms(): void
    {
        $this->actingAs($this->admin())->putJson('/api/admin/paginas/centros', [
            'idioma' => 'es', 'valores' => ['preguntas' => ['item_1_titulo' => '¿Cuánto cuesta DuaLab?']],
        ])->assertOk();

        $html = $this->get('/centros')->getContent();
        $this->assertStringContainsString('"@type":"FAQPage"', $html);
        $this->assertStringContainsString('"name":"¿Cuánto cuesta DuaLab?"', $html);
        $this->assertStringNotContainsString('FAQPage', $this->get('/noticias')->getContent());

        $this->get('/llms.txt')->assertSee("## Preguntas frecuentes\n\n### ¿Cuánto cuesta DuaLab?", false);
    }

    public function test_como_nos_ven_google_y_la_ia(): void
    {
        $editor = User::factory()->create();
        $editor->forceFill(['role' => 'editor'])->save();

        $r = $this->actingAs($editor)->getJson('/api/admin/vista-buscadores?ruta=/centros')->assertOk()
            ->assertJsonPath('estado', 200)
            ->assertJsonPath('titulo', 'Centros Educativos | DuaLab')
            ->assertJsonPath('canonical', 'https://dualab.es/centros')
            ->assertJsonPath('noindex', false)
            ->assertJsonPath('encabezados.0', ['nivel' => 1, 'texto' => 'La Formación Dual más conectada, simple y eficaz']);
        $this->assertContains('FAQPage', $r->json('datos'));
        $this->assertGreaterThan(300, $r->json('palabras'));
        $this->assertStringNotContainsString('Empresas Alumnado', $r->json('texto')); // sin el menú

        $this->actingAs($editor)->getJson('/api/admin/vista-buscadores?ruta=https://otra.web/')->assertUnprocessable();
        $alumno = User::factory()->create();
        $alumno->forceFill(['role' => 'alumno'])->save();
        $this->actingAs($alumno)->getJson('/api/admin/vista-buscadores?ruta=/centros')->assertForbidden();
    }

    public function test_noticia_renombrada_redirige_a_su_nueva_direccion(): void
    {
        $admin = $this->admin();
        $id = $this->actingAs($admin)->postJson('/api/admin/entradas', [
            'idioma' => 'es', 'titulo' => 'Primera', 'estado' => 'publicada',
        ])->assertCreated()->json('id') ?? \App\Models\Entrada::value('id');

        $this->actingAs($admin)->putJson("/api/admin/entradas/$id", ['slug' => 'segunda'])->assertOk();
        $this->get('/noticias/primera')->assertStatus(301)->assertRedirect('/noticias/segunda');

        // Otro cambio: la primera dirección va directa a la última (sin cadenas)
        $this->actingAs($admin)->putJson("/api/admin/entradas/$id", ['slug' => 'tercera'])->assertOk();
        $this->get('/noticias/primera')->assertRedirect('/noticias/tercera');
        $this->get('/noticias/segunda')->assertRedirect('/noticias/tercera');

        // Volver a la primera: sin bucle
        $this->actingAs($admin)->putJson("/api/admin/entradas/$id", ['slug' => 'primera'])->assertOk();
        $this->get('/noticias/primera')->assertOk();
        $this->get('/noticias/tercera')->assertRedirect('/noticias/primera');
    }

    public function test_borrador_renombrado_no_deja_redireccion(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->postJson('/api/admin/entradas', ['idioma' => 'es', 'titulo' => 'Borrador', 'estado' => 'borrador'])->assertCreated();
        $id = \App\Models\Entrada::value('id');

        $this->actingAs($admin)->putJson("/api/admin/entradas/$id", ['slug' => 'otro'])->assertOk();
        $this->assertDatabaseCount('redirecciones', 0);
    }

    public function test_fichas_sin_texto_en_el_panel_llevan_la_cabecera_general(): void
    {
        $html = $this->get('/familias/informatica')->assertOk()->getContent();
        $this->assertStringContainsString('<title>DuaLab</title>', $html);
        $this->assertStringContainsString('<link rel="canonical" href="https://dualab.es/familias/informatica" />', $html);
    }
}
