<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisibilidadTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol): User
    {
        $u = User::factory()->create();
        $u->forceFill(['role' => $rol])->save();

        return $u;
    }

    public function test_robots_enlaza_el_sitemap_y_bloquea_los_bots_elegidos(): void
    {
        $base = config('cms.url_publica');

        $this->get('/robots.txt')->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee("Sitemap: $base/sitemap.xml", false)
            ->assertSee('Disallow: /gestion-web', false)
            ->assertDontSee('GPTBot')
            ->assertHeaderMissing('Set-Cookie'); // sin sesión ni cookies: lo piden bots

        $this->actingAs($this->usuario('admin'))->putJson('/api/admin/ajustes/seo', [
            'ajustes' => ['ia_bloqueados' => ['GPTBot', 'CCBot']],
        ])->assertOk()->assertJsonPath('ia_bloqueados', ['GPTBot', 'CCBot']);

        $this->get('/robots.txt')->assertSee("User-agent: GPTBot\nDisallow: /", false)
            ->assertSee("User-agent: CCBot\nDisallow: /", false)
            ->assertDontSee('ClaudeBot');

        $this->actingAs($this->usuario('admin'))->putJson('/api/admin/ajustes/seo', ['ajustes' => ['indexar' => false]])->assertOk();
        $this->get('/robots.txt')->assertSee("User-agent: *\nDisallow: /\n", false)->assertDontSee('Sitemap');
    }

    public function test_solo_bots_conocidos_y_solo_el_admin_los_bloquea(): void
    {
        $this->actingAs($this->usuario('admin'))->putJson('/api/admin/ajustes/seo', [
            'ajustes' => ['ia_bloqueados' => ["Googlebot\nDisallow: /"]],
        ])->assertUnprocessable();

        $this->actingAs($this->usuario('editor'))->putJson('/api/admin/ajustes/seo', [
            'ajustes' => ['ia_bloqueados' => ['GPTBot']],
        ])->assertUnprocessable();
    }

    public function test_datos_de_la_organizacion_van_al_json_ld(): void
    {
        $this->getJson('/api/web/configuracion')->assertOk()->assertJsonPath('organizacion', null);

        $this->actingAs($this->usuario('editor'))->putJson('/api/admin/ajustes/seo', ['ajustes' => [
            'org_nombre' => 'DuaLab', 'org_email' => 'info@dualab.es', 'org_localidad' => 'Las Palmas de Gran Canaria',
            'org_redes' => ['https://www.linkedin.com/company/dualab'],
        ]])->assertOk();

        $this->getJson('/api/web/configuracion')
            ->assertJsonPath('organizacion.@type', 'Organization')
            ->assertJsonPath('organizacion.name', 'DuaLab')
            ->assertJsonPath('organizacion.address.addressLocality', 'Las Palmas de Gran Canaria')
            ->assertJsonPath('organizacion.sameAs.0', 'https://www.linkedin.com/company/dualab');

        $this->actingAs($this->usuario('editor'))->putJson('/api/admin/ajustes/seo', ['ajustes' => [
            'org_redes' => ['javascript:alert(1)'],
        ]])->assertUnprocessable()->assertJsonValidationErrors('ajustes.org_redes.0');
    }

    public function test_llms_resume_la_web_y_se_actualiza_con_las_noticias(): void
    {
        $admin = $this->usuario('admin');
        $this->actingAs($admin)->putJson('/api/admin/ajustes/seo', ['ajustes' => [
            'org_nombre' => 'DuaLab', 'descripcion' => 'Retos reales de empresas para la FP dual.',
            'llms_resumen' => 'DuaLab conecta centros de FP con empresas.',
        ]])->assertOk();

        $this->get('/llms.txt')->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
            ->assertSee("# DuaLab\n\n> Retos reales de empresas para la FP dual.", false)
            ->assertSee('DuaLab conecta centros de FP con empresas.')
            ->assertSee('/empresas-asociadas)', false)
            ->assertDontSee('Noticia de prueba');

        $this->actingAs($admin)->postJson('/api/admin/entradas', [
            'idioma' => 'es', 'titulo' => 'Noticia de prueba', 'estado' => 'publicada',
        ])->assertCreated();

        $this->get('/llms.txt')->assertSee('[Noticia de prueba](', false);
        $this->get('/sitemap.xml')->assertOk()->assertSee('/noticias/noticia-de-prueba', false);
    }
}
