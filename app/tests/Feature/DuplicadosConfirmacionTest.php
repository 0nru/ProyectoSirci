<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DuplicadosConfirmacionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Prohibido preparar datos de prueba en MariaDB.
        if (
            DB::connection()->getDriverName() !== 'sqlite' ||
            DB::connection()->getDatabaseName() !== ':memory:'
        ) {
            throw new \RuntimeException(
                'Estas pruebas requieren SQLite en memoria.'
            );
        }

        Schema::dropIfExists('reportes_duplicados');
        Schema::dropIfExists('fotos');
        Schema::dropIfExists('historial_estados');
        Schema::dropIfExists('reportes');
        Schema::dropIfExists('estados');
        Schema::dropIfExists('usuarios');

        Schema::create('usuarios', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre');
            $table->string('email')->unique();
            $table->string('password_hash');
            $table->string('rol');
            $table->boolean('activo')->default(true);
        });

        Schema::create('estados', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre');
        });

        Schema::create('reportes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('usuario_id');
            $table->unsignedInteger('categoria_id');
            $table->unsignedInteger('area_id');
            $table->unsignedInteger('estado_id');
            $table->string('descripcion');
            $table->string('barrio')->nullable();
            $table->decimal('latitud', 9, 6);
            $table->decimal('longitud', 9, 6);
            $table->dateTime('fecha_creacion');
            $table->dateTime('fecha_resolucion')->nullable();
        });

        Schema::create('historial_estados', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('reporte_id');
            $table->unsignedInteger('estado_id');
            $table->unsignedInteger('usuario_id');
            $table->string('observacion')->nullable();
            $table->dateTime('fecha_cambio')->useCurrent();
        });

        Schema::create('fotos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('reporte_id');
            $table->string('url');
            $table->unsignedInteger('orden');
        });

        // Usamos la migración real de HU5, con sus
        // restricciones de unicidad y claves foráneas.
        $migracion = require database_path(
            'migrations/2026_11_05_150015_create_reportes_duplicados_table.php'
        );

        $migracion->up();

        DB::table('usuarios')->insert([
            [
                'id' => 1,
                'nombre' => 'Ciudadano Uno',
                'email' => 'ciudadano1@sirci.test',
                'password_hash' => 'hash-de-prueba',
                'rol' => 'ciudadano',
                'activo' => 1,
            ],
            [
                'id' => 2,
                'nombre' => 'Ciudadano Dos',
                'email' => 'ciudadano2@sirci.test',
                'password_hash' => 'hash-de-prueba',
                'rol' => 'ciudadano',
                'activo' => 1,
            ],
            [
                'id' => 20,
                'nombre' => 'Operador de prueba',
                'email' => 'operador@sirci.test',
                'password_hash' => 'hash-de-prueba',
                'rol' => 'operador',
                'activo' => 1,
            ],
            [
                'id' => 21,
                'nombre' => 'Supervisor de prueba',
                'email' => 'supervisor@sirci.test',
                'password_hash' => 'hash-de-prueba',
                'rol' => 'supervisor',
                'activo' => 1,
            ],
        ]);

        DB::table('estados')->insert([
            ['id' => 1, 'nombre' => 'recibido'],
            ['id' => 2, 'nombre' => 'en proceso'],
            ['id' => 3, 'nombre' => 'resuelto'],
        ]);

        // Principal más antiguo.
        $this->crearReporte(10, 6);

        // Otro posible principal, más reciente que el 10.
        $this->crearReporte(16, 5, 1, 1, -26.18482);

        // Duplicado cercano.
        $this->crearReporte(11, 4, 1, 1, -26.18480);

        // Otro duplicado cercano, en proceso.
        $this->crearReporte(12, 3, 1, 2, -26.18475);

        // Misma ubicación, distinta categoría.
        $this->crearReporte(13, 2, 2, 1, -26.18481);

        // Misma categoría, pero demasiado lejos.
        $this->crearReporte(14, 2, 1, 1, -26.18590);

        // Reclamo resuelto.
        $this->crearReporte(15, 2, 1, 3, -26.18483);

        // Historial y fotografías preexistentes.
        DB::table('historial_estados')->insert([
            [
                'reporte_id' => 10,
                'estado_id' => 1,
                'usuario_id' => 1,
                'observacion' => 'Registro original',
            ],
            [
                'reporte_id' => 11,
                'estado_id' => 1,
                'usuario_id' => 2,
                'observacion' => 'Registro independiente',
            ],
        ]);

        DB::table('fotos')->insert([
            [
                'reporte_id' => 10,
                'url' => 'reportes/10/original.png',
                'orden' => 1,
            ],
            [
                'reporte_id' => 11,
                'url' => 'reportes/11/original.png',
                'orden' => 1,
            ],
        ]);
    }

    private function crearReporte(
        int $id,
        int $diasAtras,
        int $categoria = 1,
        int $estado = 1,
        float $latitud = -26.18490
    ): void {
        DB::table('reportes')->insert([
            'id' => $id,
            'usuario_id' => $id === 10 ? 1 : 2,
            'categoria_id' => $categoria,
            'area_id' => 1,
            'estado_id' => $estado,
            'descripcion' => 'Incidencia de prueba ' . $id,
            'barrio' => 'Dirección de prueba',
            'latitud' => $latitud,
            'longitud' => -58.17310,
            'fecha_creacion' => now()
                ->subDays($diasAtras)
                ->format('Y-m-d H:i:s'),
            'fecha_resolucion' => $estado === 3
                ? now()->format('Y-m-d H:i:s')
                : null,
        ]);
    }

    private function confirmar(
        int $reporte,
        int $similar,
        string $rol = 'operador',
        array $datos = []
    ): TestResponse {
        $usuarioId = match ($rol) {
            'ciudadano' => 1,
            'supervisor' => 21,
            default => 20,
        };

        return $this->actingAs(User::findOrFail($usuarioId))
            ->post(
                "/gestion/{$reporte}/duplicados/{$similar}",
                $datos
            );
    }

    public function test_confirma_duplicado_y_conserva_los_registros(): void
    {
        // Enviamos primero el reclamo más reciente.
        $this->confirmar(11, 10, 'operador', [
            'observacion_duplicado' => 'Mismo problema',
        ])->assertRedirect('/gestion/11');

        $this->assertDatabaseHas('reportes_duplicados', [
            'reporte_principal_id' => 10,
            'reporte_duplicado_id' => 11,
            'confirmado_por' => 20,
            'observacion' => 'Mismo problema',
        ]);

        $this->assertNotNull(
            DB::table('reportes_duplicados')
                ->value('fecha_confirmacion')
        );

        // No elimina ni fusiona los reportes.
        $this->assertDatabaseCount('reportes', 7);

        $this->assertDatabaseHas('reportes', [
            'id' => 10,
            'usuario_id' => 1,
            'estado_id' => 1,
        ]);

        $this->assertDatabaseHas('reportes', [
            'id' => 11,
            'usuario_id' => 2,
            'estado_id' => 1,
        ]);

        // Conserva sus historiales.
        $this->assertDatabaseCount('historial_estados', 2);

        $this->assertDatabaseHas('historial_estados', [
            'reporte_id' => 11,
            'observacion' => 'Registro independiente',
        ]);

        // Conserva las referencias a sus fotografías.
        $this->assertDatabaseCount('fotos', 2);

        $this->assertDatabaseHas('fotos', [
            'reporte_id' => 10,
            'url' => 'reportes/10/original.png',
        ]);

        $this->assertDatabaseHas('fotos', [
            'reporte_id' => 11,
            'url' => 'reportes/11/original.png',
        ]);
    }

    public function test_repetir_confirmacion_no_crea_otro_vinculo(): void
    {
        $this->confirmar(10, 11)->assertRedirect();

        // Repetimos invirtiendo el orden de los IDs.
        $this->confirmar(11, 10)->assertRedirect();

        $this->assertDatabaseCount('reportes_duplicados', 1);
    }

    public function test_un_principal_admite_varios_duplicados(): void
    {
        $this->confirmar(10, 11)->assertRedirect();
        $this->confirmar(10, 12, 'supervisor')
            ->assertRedirect();

        $this->assertDatabaseHas('reportes_duplicados', [
            'reporte_principal_id' => 10,
            'reporte_duplicado_id' => 11,
        ]);

        $this->assertDatabaseHas('reportes_duplicados', [
            'reporte_principal_id' => 10,
            'reporte_duplicado_id' => 12,
            'confirmado_por' => 21,
        ]);

        $this->assertDatabaseCount('reportes_duplicados', 2);
    }

    public function test_rechaza_categorias_diferentes(): void
    {
        $this->confirmar(10, 13)
            ->assertSessionHasErrors('duplicado');

        $this->assertDatabaseCount('reportes_duplicados', 0);
    }

    public function test_rechaza_un_reclamo_resuelto(): void
    {
        $this->confirmar(10, 15)
            ->assertSessionHasErrors('duplicado');

        $this->assertDatabaseCount('reportes_duplicados', 0);
    }

    public function test_rechaza_reclamos_fuera_del_radio(): void
    {
        $this->confirmar(10, 14)
            ->assertSessionHasErrors('duplicado');

        $this->assertDatabaseCount('reportes_duplicados', 0);
    }

    public function test_rechaza_duplicarse_a_si_mismo(): void
    {
        $this->confirmar(10, 10)
            ->assertSessionHasErrors('duplicado');

        $this->assertDatabaseCount('reportes_duplicados', 0);
    }

    public function test_impide_cadenas_de_duplicados(): void
    {
        // El 11 queda vinculado al principal 10.
        $this->confirmar(10, 11)->assertRedirect();

        // El 11 no puede convertirse en principal del 12.
        $this->confirmar(11, 12)
            ->assertSessionHasErrors('duplicado');

        $this->assertDatabaseCount('reportes_duplicados', 1);
    }

    public function test_impide_cambiar_el_principal_de_un_duplicado(): void
    {
        $this->confirmar(10, 11)->assertRedirect();

        // Intentamos vincular el 11 con el principal 16.
        $this->confirmar(16, 11)
            ->assertSessionHasErrors('duplicado');

        $this->assertDatabaseCount('reportes_duplicados', 1);

        $this->assertDatabaseHas('reportes_duplicados', [
            'reporte_principal_id' => 10,
            'reporte_duplicado_id' => 11,
        ]);
    }

    public function test_ciudadano_no_puede_confirmar_duplicados(): void
    {
        $this->confirmar(10, 11, 'ciudadano')
            ->assertForbidden();

        $this->assertDatabaseCount('reportes_duplicados', 0);
    }

    public function test_reclamo_inexistente_devuelve_404(): void
    {
        $this->confirmar(10, 999)
            ->assertNotFound();

        $this->assertDatabaseCount('reportes_duplicados', 0);
    }

    public function test_rechaza_observacion_demasiado_extensa(): void
    {
        $this->confirmar(10, 11, 'operador', [
            'observacion_duplicado' => str_repeat('A', 501),
        ])->assertSessionHasErrors('observacion_duplicado');

        $this->assertDatabaseCount('reportes_duplicados', 0);
    }
}
