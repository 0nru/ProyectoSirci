<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EstadosNotificacionesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Nunca ejecutar esta preparación sobre MariaDB.
        if (
            DB::connection()->getDriverName() !== 'sqlite' ||
            DB::connection()->getDatabaseName() !== ':memory:'
        ) {
            throw new \RuntimeException(
                'Estas pruebas requieren SQLite en memoria.'
            );
        }

        // Tablas mínimas para probar el flujo de estados.
        Schema::dropIfExists('notificaciones');
        Schema::dropIfExists('historial_estados');
        Schema::dropIfExists('reportes');
        Schema::dropIfExists('estados');
        Schema::dropIfExists('categorias');

        Schema::create('estados', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre');
        });

        Schema::create('categorias', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre');
        });

        Schema::create('reportes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('usuario_id');
            $table->unsignedInteger('categoria_id');
            $table->unsignedInteger('area_id');
            $table->unsignedInteger('estado_id');
            $table->string('descripcion')->nullable();
            $table->decimal('latitud', 9, 6);
            $table->decimal('longitud', 9, 6);
            $table->dateTime('fecha_creacion');
            $table->dateTime('fecha_resolucion')->nullable();
        });

        Schema::create('historial_estados', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('reporte_id');
            $table->unsignedInteger('estado_id');
            $table->unsignedInteger('area_id');
            $table->unsignedInteger('usuario_id');
            $table->string('observacion')->nullable();
            $table->dateTime('fecha_cambio');
        });

        Schema::create('notificaciones', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('usuario_id');
            $table->unsignedInteger('reporte_id');
            $table->string('mensaje');
            $table->boolean('leida');
            $table->dateTime('fecha');
        });

        DB::table('estados')->insert([
            ['id' => 1, 'nombre' => 'recibido'],
            ['id' => 2, 'nombre' => 'en proceso'],
            ['id' => 3, 'nombre' => 'resuelto'],
        ]);

        DB::table('categorias')->insert([
            'id' => 1,
            'nombre' => 'Alumbrado público',
        ]);

        // Reclamo ficticio que pertenece al ciudadano ID 1.
        DB::table('reportes')->insert([
            'id' => 10,
            'usuario_id' => 1,
            'categoria_id' => 1,
            'area_id' => 1,
            'estado_id' => 2,
            'descripcion' => 'Luminaria sin funcionamiento',
            'latitud' => -26.184900,
            'longitud' => -58.173100,
            'fecha_creacion' => now()->subDays(2),
            'fecha_resolucion' => null,
        ]);
    }

    private function usuario(string $rol, int $id): User
    {
        $usuario = new User();

        $usuario->forceFill([
            'id' => $id,
            'nombre' => 'Usuario de prueba',
            'rol' => $rol,
            'activo' => true,
        ]);

        return $usuario;
    }

    private function marcarComoResuelto(): void
    {
        DB::table('reportes')->where('id', 10)->update([
            'estado_id' => 3,
            'fecha_resolucion' => now(),
        ]);
    }

    public function test_operador_resuelve_y_genera_historial_y_notificacion(): void
    {
        $this->actingAs($this->usuario('operador', 2))
            ->post('/gestion/10/estado', [
                'estado' => 'resuelto',
                'observacion' => 'Reparación completada',
            ])
            ->assertRedirect('/gestion/10');

        $this->assertDatabaseHas('reportes', [
            'id' => 10,
            'estado_id' => 3,
        ]);

        $this->assertNotNull(
            DB::table('reportes')->where('id', 10)
                ->value('fecha_resolucion')
        );

        $this->assertDatabaseHas('historial_estados', [
            'reporte_id' => 10,
            'estado_id' => 3,
            'usuario_id' => 2,
            'observacion' => 'Reparación completada',
        ]);

        $this->assertDatabaseHas('notificaciones', [
            'usuario_id' => 1,
            'reporte_id' => 10,
            'leida' => 0,
        ]);
    }

    public function test_operador_no_puede_reabrir_resuelto(): void
    {
        $this->marcarComoResuelto();

        $this->actingAs($this->usuario('operador', 2))
            ->post('/gestion/10/estado', [
                'estado' => 'en proceso',
            ])
            ->assertSessionHasErrors('estado');

        $this->assertDatabaseHas('reportes', [
            'id' => 10,
            'estado_id' => 3,
        ]);

        $this->assertDatabaseCount('historial_estados', 0);
        $this->assertDatabaseCount('notificaciones', 0);
    }

    public function test_supervisor_puede_reabrir_resuelto(): void
    {
        $this->marcarComoResuelto();

        $this->actingAs($this->usuario('supervisor', 3))
            ->post('/gestion/10/estado', [
                'estado' => 'en proceso',
            ])
            ->assertRedirect('/gestion/10');

        $this->assertDatabaseHas('reportes', [
            'id' => 10,
            'estado_id' => 2,
            'fecha_resolucion' => null,
        ]);

        $this->assertDatabaseHas('historial_estados', [
            'reporte_id' => 10,
            'estado_id' => 2,
            'usuario_id' => 3,
        ]);

        $this->assertDatabaseCount('notificaciones', 1);
    }

    public function test_ciudadano_no_puede_modificar_estados(): void
    {
        $this->actingAs($this->usuario('ciudadano', 1))
            ->post('/gestion/10/estado', [
                'estado' => 'resuelto',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('reportes', [
            'id' => 10,
            'estado_id' => 2,
        ]);

        $this->assertDatabaseCount('notificaciones', 0);
    }

    public function test_no_permite_saltar_de_recibido_a_resuelto(): void
    {
        DB::table('reportes')->where('id', 10)
            ->update(['estado_id' => 1]);

        $this->actingAs($this->usuario('supervisor', 3))
            ->post('/gestion/10/estado', [
                'estado' => 'resuelto',
            ])
            ->assertSessionHasErrors('estado');

        $this->assertDatabaseHas('reportes', [
            'id' => 10,
            'estado_id' => 1,
        ]);

        $this->assertDatabaseCount('notificaciones', 0);
    }
}
