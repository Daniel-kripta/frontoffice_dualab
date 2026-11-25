<?php

namespace Tests\Feature;

use App\Models\ActividadPanel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Historial del panel: cada acción guarda qué cambió (antes → después) sin copiar secretos ni correos. */
class HistorialPanelTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol): User
    {
        $u = User::factory()->create();
        $u->forceFill(['role' => $rol])->save();

        return $u;
    }

    private function ultimoDetalle(): array
    {
        return ActividadPanel::latest('id')->first()->detalle ?? [];
    }

    public function test_editar_pagina_guarda_antes_y_despues(): void
    {
        $admin = $this->usuario('admin');
        $this->actingAs($admin)->putJson('/api/admin/paginas/centros', [
            'idioma' => 'es', 'valores' => ['hero' => ['titulo' => 'Nuevo título']], 'seo' => ['meta_titulo' => 'FP dual para centros'],
        ])->assertOk();

        $detalle = collect($this->ultimoDetalle())->keyBy('campo');
        $this->assertSame(['campo' => 'Cabecera · Título', 'antes' => 'La Formación Dual más conectada,', 'despues' => 'Nuevo título'], $detalle['Cabecera · Título']);
        $this->assertSame([null, 'FP dual para centros'], [$detalle['SEO · Título']['antes'], $detalle['SEO · Título']['despues']]);
        $this->assertCount(2, $detalle); // solo lo que cambió

        // El listado del historial lo devuelve
        $this->actingAs($admin)->getJson('/api/admin/actividad')->assertOk()
            ->assertJsonPath('data.0.detalle.0.campo', 'Cabecera · Título');
    }

    public function test_entrada_resume_el_contenido_y_la_nota(): void
    {
        $admin = $this->usuario('admin');
        $id = $this->actingAs($admin)->postJson('/api/admin/entradas', [
            'idioma' => 'es', 'titulo' => 'Primera', 'estado' => 'borrador', 'contenido' => '<p>Uno</p>',
        ])->assertCreated()->json('id') ?? \App\Models\Entrada::value('id');

        $this->actingAs($admin)->putJson("/api/admin/entradas/$id", ['titulo' => 'Segunda', 'contenido' => '<p>Dos</p>'])->assertOk();
        $detalle = collect($this->ultimoDetalle())->keyBy('campo');
        $this->assertSame(['Primera', 'Segunda'], [$detalle['Título']['antes'], $detalle['Título']['despues']]);
        $this->assertSame([null, 'Cambiado'], [$detalle['Contenido']['antes'], $detalle['Contenido']['despues']]);
    }

    public function test_ajustes_y_usuarios_no_copian_secretos_ni_correos(): void
    {
        $admin = $this->usuario('admin');
        $this->actingAs($admin)->putJson('/api/admin/ajustes/integraciones', ['ajustes' => ['brevo_api_key' => 'xkeysib-secreta-123']])->assertOk();
        $this->assertSame([['campo' => 'Clave de Brevo', 'antes' => null, 'despues' => 'Añadido']], $this->ultimoDetalle());

        $this->actingAs($admin)->putJson('/api/admin/ajustes/seo', ['ajustes' => ['org_nombre' => 'DuaLab']])->assertOk();
        $this->assertSame([['campo' => 'Organización · Nombre', 'antes' => null, 'despues' => 'DuaLab']], $this->ultimoDetalle());

        $editor = $this->usuario('editor');
        $this->actingAs($admin)->patchJson("/api/admin/usuarios/{$editor->id}", ['email' => 'nuevo@dualab.es', 'role' => 'admin'])->assertOk();
        $json = json_encode(ActividadPanel::latest('id')->first()->detalle);
        $this->assertStringNotContainsString('nuevo@dualab.es', $json);
        $this->assertStringNotContainsString($editor->email, $json);
        $this->assertStringContainsString('"despues":"Cambiado"', $json);
        $this->assertStringContainsString('"campo":"Rol"', $json);
    }
}
