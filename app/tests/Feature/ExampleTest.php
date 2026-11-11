<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * La ruta principal debe redirigir al panel.
     */
    public function test_la_ruta_principal_redirige_al_panel(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('panel'));
    }

    /**
     * Un visitante no autenticado no puede entrar al panel.
     */
    public function test_un_visitante_no_puede_acceder_al_panel(): void
    {
        $response = $this->get('/panel');

        $response->assertRedirect(route('login'));
    }
}
