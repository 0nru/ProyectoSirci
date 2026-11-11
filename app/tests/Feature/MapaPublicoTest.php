<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MapaPublicoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Nunca crear tablas de prueba sobre MariaDB.
        if (
            DB::connection()->getDriverName() !== 'sqlite' ||
            DB::connection()->getDatabaseName() !== ':memory:'
        ) {
            throw new \RuntimeException(
                'Estas pruebas requieren SQLite en memoria.'
            );
        }

        Schema::dropIfExists('reportes');
        Schema::dropIfExists('categorias');
        Schema::dropIfExists('estados');

        Schema::create('categorias', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre');
            $table->boolean('activa')->default(true);
        });

        Schema::create('estados', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre');
        });

        Schema::create('reportes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('usuario_id');
            $table->unsignedInteger('categoria_id');
            $table->unsignedInteger('estado_id');
            $table->string('descripcion');
            $table->string('barrio')->nullable();
            $table->decimal('latitud', 9, 6);
            $table->decimal('longitud', 9, 6);
            $table->dateTime('fecha_creacion');
        });

        DB::table('categorias')->insert([
            ['id' => 1, 'nombre' => 'Alumbrado', 'activa' => 1],
            ['id' => 2, 'nombre' => 'Residuos', 'activa' => 1],
        ]);

        DB::table('estados')->insert([
            ['id' => 1, 'nombre' => 'recibido'],
            ['id' => 2, 'nombre' => 'en proceso'],
            ['id' => 3, 'nombre' => 'resuelto'],
        ]);

        // Cinco reclamos ficticios con diferentes estados.
        $this->crearReporte(10, 1, 1);
        $this->crearReporte(11, 1, 2);
        $this->crearReporte(12, 1, 3);
        $this->crearReporte(13, 2, 1);
        $this->crearReporte(14, 2, 3);
    }

    private function crearReporte(
        int $id,
        int $categoria,
        int $estado
    ): void {
        DB::table('reportes')->insert([
            'id' => $id,
            'usuario_id' => 100,
            'categoria_id' => $categoria,
            'estado_id' => $estado,
            'descripcion' => 'Incidencia de prueba ' . $id,
            'barrio' => 'Referencia privada ' . $id,
            'latitud' => -26.184900,
            'longitud' => -58.173100,
            'fecha_creacion' => now()
                ->subDays(2)
                ->format('Y-m-d H:i:s'),
        ]);
    }

    private function usuario(
        string $rol = 'ciudadano',
        bool $activo = true
    ): User {
        $usuario = new User();

        $usuario->forceFill([
            'id' => 200,
            'nombre' => 'Usuario de prueba',
            'email' => 'mapa@sirci.test',
            'rol' => $rol,
            'activo' => $activo,
        ]);

        return $usuario;
    }

    private function comprobarIds(
        array $reportes,
        array $esperados
    ): void {
        $ids = array_map(
            'intval',
            array_column($reportes, 'id')
        );

        sort($ids);
        sort($esperados);

        $this->assertSame($esperados, $ids);
    }

    public function test_visitante_solo_recibe_reclamos_pendientes(): void
    {
        $respuesta = $this->getJson('/mapa/datos')
            ->assertOk()
            ->assertJsonPath('total', 3)
            ->assertJsonCount(3, 'reportes');

        $reportes = $respuesta->json('reportes');

        $this->comprobarIds($reportes, [10, 11, 13]);

        foreach ($reportes as $reporte) {
            $this->assertContains(
                $reporte['estado'],
                ['recibido', 'en proceso']
            );
        }
    }

    public function test_visitante_no_puede_forzar_filtros(): void
    {
        // Intentamos mostrar resueltos y filtrar por
        // estado y categoría sin haber iniciado sesión.
        $respuesta = $this->getJson(
            '/mapa/datos?resueltos=1&estado=resuelto&categoria=2'
        )->assertOk();

        // El servidor debe ignorar todos esos parámetros.
        $respuesta->assertJsonPath('total', 3);

        $this->comprobarIds(
            $respuesta->json('reportes'),
            [10, 11, 13]
        );
    }

    public function test_mapa_publico_no_expone_datos_adicionales(): void
    {
        $respuesta = $this->getJson('/mapa/datos')
            ->assertOk();

        foreach ($respuesta->json('reportes') as $reporte) {
            $this->assertSame(
                ['id', 'categoria', 'estado', 'lat', 'lng'],
                array_keys($reporte)
            );

            $this->assertArrayNotHasKey('barrio', $reporte);
            $this->assertArrayNotHasKey('fecha', $reporte);
            $this->assertArrayNotHasKey('url', $reporte);
            $this->assertArrayNotHasKey('usuario_id', $reporte);
        }
    }

    public function test_ciudadano_autenticado_recibe_datos_ampliados(): void
    {
        $respuesta = $this->actingAs($this->usuario())
            ->getJson('/mapa/datos')
            ->assertOk()
            ->assertJsonPath('total', 3);

        $this->comprobarIds(
            $respuesta->json('reportes'),
            [10, 11, 13]
        );

        foreach ($respuesta->json('reportes') as $reporte) {
            $this->assertArrayHasKey('barrio', $reporte);
            $this->assertArrayHasKey('fecha', $reporte);
            $this->assertArrayHasKey('hace', $reporte);
            $this->assertArrayHasKey('url', $reporte);

            // Un ciudadano no recibe enlaces administrativos.
            $this->assertNull($reporte['url']);
        }
    }

    public function test_usuario_autenticado_puede_filtrar(): void
    {
        $this->actingAs($this->usuario());

        $porCategoria = $this->getJson(
            '/mapa/datos?categoria=2'
        )->assertOk()->assertJsonPath('total', 1);

        $this->comprobarIds(
            $porCategoria->json('reportes'),
            [13]
        );

        $porEstado = $this->getJson(
            '/mapa/datos?estado=en%20proceso'
        )->assertOk()->assertJsonPath('total', 1);

        $this->comprobarIds(
            $porEstado->json('reportes'),
            [11]
        );
    }

    public function test_resueltos_requieren_solicitud_explicita(): void
    {
        $this->actingAs($this->usuario());

        // Pedir estado resuelto no basta si no se
        // activa la opción correspondiente.
        $sinHabilitar = $this->getJson(
            '/mapa/datos?estado=resuelto'
        )->assertOk()->assertJsonPath('total', 0);

        $this->assertSame(
            [],
            $sinHabilitar->json('reportes')
        );

        // Con resueltos=1 sí deben aparecer.
        $respuesta = $this->getJson(
            '/mapa/datos?resueltos=1&estado=resuelto'
        )->assertOk()->assertJsonPath('total', 2);

        $this->comprobarIds(
            $respuesta->json('reportes'),
            [12, 14]
        );

        // Sin filtro de estado, resueltos=1 incluye todos.
        $todos = $this->getJson(
            '/mapa/datos?resueltos=1'
        )->assertOk()->assertJsonPath('total', 5);

        $this->comprobarIds(
            $todos->json('reportes'),
            [10, 11, 12, 13, 14]
        );
    }

    public function test_operador_recibe_enlaces_a_gestion(): void
    {
        $respuesta = $this->actingAs(
            $this->usuario('operador')
        )->getJson('/mapa/datos')->assertOk();

        foreach ($respuesta->json('reportes') as $reporte) {
            $this->assertSame(
                url('/gestion/' . $reporte['id']),
                $reporte['url']
            );
        }
    }

    public function test_usuario_inactivo_no_puede_consultar_datos(): void
    {
        $this->actingAs(
            $this->usuario('operador', false)
        )->getJson('/mapa/datos')
            ->assertStatus(401)
            ->assertJsonPath(
                'mensaje',
                'La cuenta está desactivada.'
            );

        $this->assertGuest();
    }
}
