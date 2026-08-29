<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * La raíz de LexCita redirige a la pantalla de login
     * para usuarios no autenticados (HTTP 302).
     */
    public function test_la_raiz_redirige_al_login(): void
    {
        $response = $this->get('/');

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }
}