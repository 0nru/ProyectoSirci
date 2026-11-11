<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RegistroCiudadanoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Protección: nunca crear tablas de prueba en MariaDB.
        if (
            DB::connection()->getDriverName() !== 'sqlite' ||
            DB::connection()->getDatabaseName() !== ':memory:'
        ) {
            throw new \RuntimeException(
                'Estas pruebas requieren SQLite en memoria.'
            );
        }

        // Esquema mínimo de usuarios para pruebas aisladas.
        Schema::dropIfExists('usuarios');

        Schema::create('usuarios', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre', 100);
            $table->string('email', 150)->unique();
            $table->string('telefono', 30)->nullable();
            $table->string('password_hash');
            $table->string('rol')->default('ciudadano');
            $table->boolean('activo')->default(true);
            $table->dateTime('fecha_registro')->nullable();
        });
    }

    private function datosValidos(): array
    {
        return [
            'nombre' => 'Ciudadano de prueba',
            'email' => 'nuevo@sirci.test',
            'telefono' => null,
            'password' => 'ClaveSegura123!',
            'password_confirmation' => 'ClaveSegura123!',
            'terminos' => '1',
        ];
    }

    public function test_registro_crea_un_ciudadano_autenticado(): void
    {
        $this->post('/registro', $this->datosValidos())
            ->assertRedirect('/panel');

        $usuario = DB::table('usuarios')
            ->where('email', 'nuevo@sirci.test')
            ->first();

        $this->assertNotNull($usuario);
        $this->assertSame('ciudadano', $usuario->rol);
        $this->assertSame(1, (int) $usuario->activo);

        // La contraseña nunca debe guardarse en texto plano.
        $this->assertNotSame('ClaveSegura123!', $usuario->password_hash);
        $this->assertTrue(
            Hash::check('ClaveSegura123!', $usuario->password_hash)
        );

        $this->assertAuthenticated();
    }

    public function test_no_permite_asignarse_rol_supervisor(): void
    {
        $datos = $this->datosValidos();

        // Simulamos que alguien manipula el formulario.
        $datos['rol'] = 'supervisor';
        $datos['activo'] = 0;

        $this->post('/registro', $datos)
            ->assertRedirect('/panel');

        $usuario = DB::table('usuarios')
            ->where('email', 'nuevo@sirci.test')
            ->first();

        $this->assertNotNull($usuario);
        $this->assertSame('ciudadano', $usuario->rol);
        $this->assertSame(1, (int) $usuario->activo);
    }

    public function test_rechaza_contrasenas_que_no_coinciden(): void
    {
        $datos = $this->datosValidos();
        $datos['password_confirmation'] = 'OtraClave123!';

        $this->post('/registro', $datos)
            ->assertSessionHasErrors('password');

        $this->assertSame(0, DB::table('usuarios')->count());
    }
}
