<?php

namespace Tests\Feature;

use App\Models\ActividadPanel;
use App\Models\Contacto;
use App\Models\User;
use App\Notifications\InvitacionGestionWeb;
use App\Notifications\RestablecerContrasenaGestionWeb;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/** Roles del panel, usuarios gestionados por el admin y registro de actividad. */
class UsuariosPanelTest extends TestCase
{
    use RefreshDatabase;

    private const CONTRASENA = 'Segura-123!';

    private function usuario(string $rol, array $extra = []): User
    {
        $u = User::create(['name' => ucfirst($rol).' Prueba', 'email' => $rol.uniqid().'@dualab.es', 'password' => Hash::make(self::CONTRASENA)]);
        $u->forceFill(['role' => $rol, 'email_verified_at' => now()] + $extra)->save();

        return $u;
    }

    // Atención al cliente ya no es un rol de Gestión web (trabaja en DuaLab Central): una cuenta que
    // quedara con ese rol no entra en el panel, y no se puede crear
    public function test_atencion_al_cliente_ya_no_entra_en_el_panel(): void
    {
        $contactos = $this->usuario('atencion_cliente');

        $this->actingAs($contactos)->getJson('/api/admin/perfil')->assertForbidden();
        $this->actingAs($contactos)->getJson('/api/admin/escritorio')->assertForbidden();
        $this->actingAs($this->usuario('admin'))->postJson('/api/admin/usuarios', [
            'name' => 'Luis', 'email' => 'luis@dualab.es', 'role' => 'atencion_cliente', 'password' => self::CONTRASENA, 'password_confirmation' => self::CONTRASENA,
        ])->assertUnprocessable()->assertJsonValidationErrors('role');
        $this->actingAs($contactos)->getJson('/api/admin/paginas')->assertForbidden();
        $this->actingAs($contactos)->getJson('/api/admin/medios')->assertForbidden();
        $this->actingAs($contactos)->getJson('/api/admin/usuarios')->assertForbidden();
        $this->actingAs($this->usuario('editor'))->getJson('/api/admin/usuarios')->assertForbidden();
        $this->actingAs($this->usuario('editor'))->getJson('/api/admin/actividad')->assertForbidden();
    }

    public function test_alta_como_en_la_app_dualab_con_activacion_y_correo_de_acceso(): void
    {
        Notification::fake();
        $admin = $this->usuario('admin');
        $alta  = ['name' => 'Ana', 'email' => 'Ana@DuaLab.es', 'role' => 'editor', 'password' => self::CONTRASENA, 'password_confirmation' => self::CONTRASENA];

        $this->actingAs($admin)->postJson('/api/admin/usuarios', ['password' => 'corta'] + $alta)->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->actingAs($admin)->postJson('/api/admin/usuarios', $alta)
            ->assertCreated()->assertJsonPath('pendiente_activar', true)->assertJsonPath('email', 'ana@dualab.es');

        $ana = User::where('email', 'ana@dualab.es')->first();
        $this->assertSame('editor', $ana->role);
        $this->assertNull($ana->email_verified_at);
        Notification::assertSentTo($ana, InvitacionGestionWeb::class);
        $this->assertDatabaseHas('actividad_panel', ['user_id' => $admin->id, 'seccion' => 'usuarios', 'accion' => 'crear']);

        // Sin activar no entra (como en la app DuaLab); el admin la activa y ya puede
        auth()->forgetGuards();
        $login = fn () => $this->withHeader('Referer', 'http://localhost:5174')->postJson('/api/auth/login', ['email' => 'ana@dualab.es', 'password' => self::CONTRASENA]);
        $login()->assertUnprocessable();
        $this->actingAs($admin)->postJson("/api/admin/usuarios/{$ana->id}/activar")->assertOk()->assertJsonPath('pendiente_activar', false);
        $this->assertNotNull($ana->fresh()->email_verified_at); // login con cuenta activada: LoginTest

        // Email repetido (también en papelera o cuentas antiguas): no
        $this->actingAs($admin)->postJson('/api/admin/usuarios', ['email' => 'ana@dualab.es'] + $alta)->assertUnprocessable();
    }

    public function test_el_enlace_del_correo_crea_la_contrasena_y_activa_la_cuenta(): void
    {
        Notification::fake();
        $admin = $this->usuario('admin');
        $this->actingAs($admin)->postJson('/api/admin/usuarios', [
            'name' => 'Luis', 'email' => 'luis@dualab.es', 'role' => 'editor', 'password' => self::CONTRASENA, 'password_confirmation' => self::CONTRASENA,
        ])->assertCreated();
        $luis = User::where('email', 'luis@dualab.es')->first();

        $token = Password::broker('invitaciones')->createToken($luis);
        $nueva = ['token' => $token, 'email' => 'luis@dualab.es', 'modo' => 'invitacion', 'password' => 'Propia-456?', 'password_confirmation' => 'Propia-456?'];
        $this->postJson('/api/auth/crear-contrasena', $nueva)->assertOk();

        $this->assertNotNull($luis->fresh()->email_verified_at);
        $this->assertTrue(Hash::check('Propia-456?', $luis->fresh()->password));
        $this->postJson('/api/auth/crear-contrasena', $nueva)->assertUnprocessable(); // el enlace solo sirve una vez

        // El admin cambia la contraseña de otra cuenta (no la suya: eso va en Mi perfil)
        $this->actingAs($admin)->patchJson("/api/admin/usuarios/{$luis->id}", ['password' => self::CONTRASENA, 'password_confirmation' => self::CONTRASENA])->assertOk();
        $this->assertTrue(Hash::check(self::CONTRASENA, $luis->fresh()->password));
        $this->actingAs($admin)->patchJson("/api/admin/usuarios/{$admin->id}", ['password' => 'Otra-789!x', 'password_confirmation' => 'Otra-789!x'])->assertUnprocessable();
    }

    public function test_olvide_contrasena_no_dice_si_el_email_existe(): void
    {
        Notification::fake();
        $editor = $this->usuario('editor');
        $alumno = $this->usuario('alumno');
        $bloqueado = $this->usuario('editor', ['blocked' => true]);

        foreach (['nadie@dualab.es', $editor->email, $alumno->email, $bloqueado->email] as $email) {
            $this->postJson('/api/auth/olvide-contrasena', ['email' => $email])->assertOk()
                ->assertJsonPath('message', 'Si existe una cuenta del panel con ese email, recibirás un enlace para elegir una contraseña nueva.');
        }

        Notification::assertSentTo($editor, RestablecerContrasenaGestionWeb::class);
        Notification::assertNotSentTo($alumno, RestablecerContrasenaGestionWeb::class);
        Notification::assertNotSentTo($bloqueado, RestablecerContrasenaGestionWeb::class);
    }

    public function test_protecciones_del_admin_y_cuentas_ajenas_al_panel(): void
    {
        $admin  = $this->usuario('admin');
        $editor = $this->usuario('editor');
        $alumno = $this->usuario('alumno');

        // Nadie sobre sí mismo y siempre queda un admin
        $this->actingAs($admin)->postJson("/api/admin/usuarios/{$admin->id}/bloquear")->assertUnprocessable();
        $this->actingAs($admin)->deleteJson("/api/admin/usuarios/{$admin->id}")->assertUnprocessable();
        $this->actingAs($admin)->patchJson("/api/admin/usuarios/{$admin->id}", ['role' => 'editor'])->assertUnprocessable();

        // Las cuentas antiguas de la web no se ven ni se tocan desde aquí
        $ids = collect($this->actingAs($admin)->getJson('/api/admin/usuarios')->assertOk()->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($editor->id));
        $this->assertFalse($ids->contains($alumno->id));
        $this->actingAs($admin)->patchJson("/api/admin/usuarios/{$alumno->id}", ['role' => 'admin'])->assertNotFound();
        $this->assertSame('alumno', $alumno->fresh()->role);

        // Cambiar rol, bloquear (no puede entrar) y papelera → restaurar
        $this->actingAs($admin)->patchJson("/api/admin/usuarios/{$editor->id}", ['role' => 'admin'])->assertOk()->assertJsonPath('role', 'admin');
        $this->actingAs($admin)->postJson("/api/admin/usuarios/{$editor->id}/bloquear")->assertOk()->assertJsonPath('bloqueado', true);
        auth()->forgetGuards();
        $this->withHeader('Referer', 'http://localhost:5174')
            ->postJson('/api/auth/login', ['email' => $editor->email, 'password' => self::CONTRASENA])->assertUnprocessable();

        $this->actingAs($admin)->deleteJson("/api/admin/usuarios/{$editor->id}")->assertOk();
        $this->assertSoftDeleted($editor);
        $this->actingAs($admin)->getJson('/api/admin/usuarios?papelera=1')->assertOk()->assertJsonPath('data.0.id', $editor->id);
        $this->actingAs($admin)->postJson("/api/admin/usuarios/{$editor->id}/restaurar")->assertOk();
        $this->assertNotSoftDeleted($editor);
    }

    public function test_registro_de_actividad_e_historial(): void
    {
        $admin  = $this->usuario('admin');
        $editor = $this->usuario('editor');

        $this->actingAs($editor)->putJson('/api/admin/paginas/centros', [
            'idioma' => 'es', 'valores' => ['hero' => ['titulo' => 'Centros que innovan']],
        ])->assertOk();
        $contacto = Contacto::create(['nombre' => 'Eva', 'email' => 'eva@ejemplo.com', 'tipo' => 'empresa']);
        // Los formularios se atienden en DuaLab Central: queda en el registro como «Central · nombre»
        $this->central()->patchJson("/api/integraciones/central/formularios/{$contacto->id}", ['estado' => 'archivado'])->assertOk();

        $this->actingAs($editor)->getJson('/api/admin/actividad/pagina/centros')->assertOk()
            ->assertJsonPath('0.usuario.nombre', $editor->name)
            ->assertJsonPath('0.descripcion', 'editó la página «Centros Educativos»');

        $r = $this->actingAs($admin)->getJson('/api/admin/actividad?user_id='.$editor->id)->assertOk();
        $this->assertCount(1, $r->json('data'));
        $central = $this->actingAs($admin)->getJson('/api/admin/actividad?seccion=formularios')->assertOk();
        $this->assertSame("marcó el formulario #{$contacto->id} como archivado", $central->json('data.0.descripcion'));
        $this->assertStringNotContainsString('eva@ejemplo.com', $central->getContent());
        // Sin datos personales de quien escribió el formulario
        $this->assertStringNotContainsString('eva@ejemplo.com', $r->getContent());
        $this->assertSame(0, ActividadPanel::where('descripcion', 'like', '%Eva%')->count());
    }

    public function test_los_limites_de_ruta_no_se_gastan_el_doble(): void
    {
        // throttle:10,1 en /api/contacto: 10 envíos seguidos pasan la limitación (aunque fallen validando)
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/contacto', [])->assertUnprocessable();
        }
        $this->postJson('/api/contacto', [])->assertStatus(429);
    }
}
