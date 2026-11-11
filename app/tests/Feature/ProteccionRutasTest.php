<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProteccionRutasTest extends TestCase
{
    /**
     * Un visitante no puede acceder a las
     * pantallas administrativas de SIRCI.
     */
    public function test_visitante_no_puede_acceder_a_administracion(): void
    {
        $rutas = [
            '/gestion',
            '/indicadores',
            '/usuarios',
        ];

        foreach ($rutas as $ruta) {
            $this->get($ruta)
                ->assertRedirect(route('login'));
        }
    }

    /**
     * Un visitante no puede ejecutar acciones
     * administrativas enviando solicitudes POST.
     */
    public function test_visitante_no_puede_ejecutar_acciones_administrativas(): void
    {
        $this->post('/gestion/2/estado', [
            'estado' => 'resuelto',
        ])->assertRedirect(route('login'));

        $this->post('/usuarios', [
            'nombre' => 'Usuario no autorizado',
        ])->assertRedirect(route('login'));
    }
}
