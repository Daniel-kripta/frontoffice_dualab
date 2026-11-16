<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Sanctum SPA: la sesión solo se abre para peticiones del frontend (dominio stateful)
        $this->withHeader('Referer', 'http://localhost:5174');
    }

    public function test_ya_no_hay_registro_publico(): void
    {
        $this->postJson('/api/auth/register', ['name' => 'x', 'email' => 'x@x.com', 'password' => 'Secreta1!'])->assertNotFound();
    }

    public function test_un_acierto_reinicia_los_intentos_fallidos(): void
    {
        $u = User::factory()->create();
        $u->forceFill(['failed_login_attempts' => 3])->save();

        $this->postJson('/api/auth/login', ['email' => $u->email, 'password' => 'password'])->assertOk();

        $this->assertSame(0, $u->fresh()->failed_login_attempts);
    }

    public function test_sin_email_verificado_no_entra(): void
    {
        $u = User::factory()->unverified()->create();

        $this->postJson('/api/auth/login', ['email' => $u->email, 'password' => 'password'])
            ->assertUnprocessable()->assertJsonPath('errors.email.0', 'Esta cuenta aún no tiene el email verificado.');
        $this->assertGuest();
    }

    public function test_contrasena_incorrecta_mensaje_generico(): void
    {
        $u = User::factory()->create();

        $this->postJson('/api/auth/login', ['email' => $u->email, 'password' => 'mal'])
            ->assertUnprocessable()->assertJsonPath('errors.email.0', 'Las credenciales no son correctas.');
        $this->postJson('/api/auth/login', ['email' => 'no-existe@x.com', 'password' => 'mal'])
            ->assertUnprocessable()->assertJsonPath('errors.email.0', 'Las credenciales no son correctas.');
        $this->assertSame(1, $u->fresh()->failed_login_attempts);
    }
}
