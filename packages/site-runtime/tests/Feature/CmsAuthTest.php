<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['site-runtime.internal_token' => 'secreto-compartido']);
    }

    private function ssoToken(array $payload): string
    {
        $json = json_encode($payload);
        $b64 = fn ($r) => rtrim(strtr(base64_encode($r), '+/', '-_'), '=');

        return $b64($json).'.'.$b64(hash_hmac('sha256', $json, 'secreto-compartido', true));
    }

    public function test_el_cms_exige_login(): void
    {
        $this->get('/cms')->assertRedirect(route('login'));
    }

    public function test_login_con_credenciales_correctas(): void
    {
        User::factory()->create(['email' => 'a@b.com', 'password' => 'clave-larga-123']);

        $this->post('/cms/login', ['email' => 'a@b.com', 'password' => 'clave-larga-123'])
            ->assertRedirect(route('cms.index'));
        $this->assertAuthenticated();
    }

    public function test_sso_valido_abre_la_sesion_sin_password(): void
    {
        User::factory()->create(['email' => 'dueno@sitio.com']);

        $token = $this->ssoToken(['email' => 'dueno@sitio.com', 'name' => 'Leo', 'exp' => time() + 60]);

        $this->get("/cms/sso?t={$token}")->assertRedirect(route('cms.index'));
        $this->assertAuthenticated();
        $this->assertSame('dueno@sitio.com', auth()->user()->email);
    }

    public function test_sso_vencido_cae_al_login(): void
    {
        $token = $this->ssoToken(['email' => 'x@y.com', 'name' => 'X', 'exp' => time() - 10]);

        $this->get("/cms/sso?t={$token}")->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_sso_con_firma_invalida_cae_al_login(): void
    {
        $this->get('/cms/sso?t=payload.firma-trucha')->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
