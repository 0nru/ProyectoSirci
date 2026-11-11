<?php

namespace Tests\Feature;

use App\Http\Middleware\SoloMunicipio;
use App\Http\Middleware\SoloSupervisor;
use App\Http\Middleware\VerificarUsuarioActivo;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PermisosRolesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Rutas exclusivas del entorno de pruebas.
        // Evitan consultar la base de datos real.
        Route::middleware([
            'web',
            'auth',
            VerificarUsuarioActivo::class,
            SoloMunicipio::class,
        ])->get('/__pruebas/municipio', function () {
            return response('Acceso municipal autorizado', 200);
        });

        Route::middleware([
            'web',
            'auth',
            VerificarUsuarioActivo::class,
            SoloSupervisor::class,
        ])->get('/__pruebas/supervisor', function () {
            return response('Acceso supervisor autorizado', 200);
        });
    }

    // Crea un usuario simulado, sin guardarlo en la base.
    private function usuarioSimulado(
        string $rol,
        bool $activo = true
    ): User {
        $usuario = new User();

        $usuario->forceFill([
            'id' => 1000,
            'nombre' => 'Usuario de prueba',
            'email' => 'prueba@sirci.test',
            'rol' => $rol,
            'activo' => $activo,
        ]);

        return $usuario;
    }

    public function test_ciudadano_no_puede_acceder_a_gestion(): void
    {
        $this->actingAs($this->usuarioSimulado('ciudadano'))
            ->get('/gestion')
            ->assertForbidden();
    }

    public function test_ciudadano_no_puede_administrar_usuarios(): void
    {
        $this->actingAs($this->usuarioSimulado('ciudadano'))
            ->get('/usuarios')
            ->assertForbidden();
    }

    public function test_operador_puede_acceder_a_rutas_municipales(): void
    {
        $this->actingAs($this->usuarioSimulado('operador'))
            ->get('/__pruebas/municipio')
            ->assertOk();
    }

    public function test_operador_no_puede_administrar_usuarios(): void
    {
        $this->actingAs($this->usuarioSimulado('operador'))
            ->get('/usuarios')
            ->assertForbidden();
    }

    public function test_supervisor_puede_acceder_a_rutas_exclusivas(): void
    {
        $this->actingAs($this->usuarioSimulado('supervisor'))
            ->get('/__pruebas/supervisor')
            ->assertOk();
    }

    public function test_usuario_inactivo_pierde_su_sesion(): void
    {
        $this->actingAs($this->usuarioSimulado('operador', false))
            ->get('/gestion')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
