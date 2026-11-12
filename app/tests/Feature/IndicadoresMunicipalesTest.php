<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class IndicadoresMunicipalesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Fecha fija: las pruebas no dependen del día actual.
        $this->travelTo(Carbon::parse('2026-11-12 12:00:00'));

        // Protección de la base de datos real.
        if (
            DB::connection()->getDriverName() !== 'sqlite' ||
            DB::connection()->getDatabaseName() !== ':memory:'
        ) {
            throw new \RuntimeException(
                'Estas pruebas requieren SQLite en memoria.'
            );
        }

        Schema::create('estados', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre');
        });

        Schema::create('categorias', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre');
        });

        Schema::create('areas', function (Blueprint $table) {
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

        DB::table('estados')->insert([
            ['id' => 1, 'nombre' => 'recibido'],
            ['id' => 2, 'nombre' => 'en proceso'],
            ['id' => 3, 'nombre' => 'resuelto'],
        ]);

        DB::table('categorias')->insert([
            ['id' => 1, 'nombre' => 'Alumbrado'],
            ['id' => 2, 'nombre' => 'Residuos'],
        ]);

        DB::table('areas')->insert([
            ['id' => 1, 'nombre' => 'Obras Públicas'],
            ['id' => 2, 'nombre' => 'Higiene Urbana'],
        ]);

        // ID 10: recibido hace 4 días. Está demorado.
        $this->crearReporte(10, 4, 1, 1, 1, 'Centro');

        // ID 11: en proceso hace 1 día. No está demorado.
        $this->crearReporte(11, 1, 2, 1, 1, 'Centro');

        // ID 12: resuelto hace 10 días, en 2 horas y media.
        $this->crearReporte(12, 10, 3, 2, 2, 'Norte', 150);

        // ID 13: recibido hace 45 días. También está demorado.
        $this->crearReporte(13, 45, 1, 2, 2, 'Norte');

        // ID 14: resuelto hace 120 días, en 49 horas.
        $this->crearReporte(14, 120, 3, 1, 2, 'Centro', 2940);
    }

    private function crearReporte(
        int $id,
        int $diasAtras,
        int $estado,
        int $categoria,
        int $area,
        string $barrio,
        ?int $minutosResolucion = null
    ): void {
        $fecha = now()->subDays($diasAtras);

        DB::table('reportes')->insert([
            'id' => $id,
            'usuario_id' => 100,
            'categoria_id' => $categoria,
            'area_id' => $area,
            'estado_id' => $estado,
            'descripcion' => 'Reclamo ficticio ' . $id,
            'barrio' => $barrio,
            'latitud' => -26.184900,
            'longitud' => -58.173100,
            'fecha_creacion' => $fecha->format('Y-m-d H:i:s'),
            'fecha_resolucion' => $minutosResolucion === null
                ? null
                : $fecha->copy()
                    ->addMinutes($minutosResolucion)
                    ->format('Y-m-d H:i:s'),
        ]);
    }

    private function usuario(string $rol = 'operador'): User
    {
        $usuario = new User();

        $usuario->forceFill([
            'id' => 200,
            'nombre' => 'Usuario de prueba',
            'email' => 'indicadores@sirci.test',
            'rol' => $rol,
            'activo' => true,
        ]);

        return $usuario;
    }

    private function indicadores(?string $periodo = null): TestResponse
    {
        $url = '/indicadores';

        if ($periodo !== null) {
            $url .= '?periodo=' . urlencode($periodo);
        }

        return $this->actingAs($this->usuario())
            ->get($url);
    }

    public function test_periodo_predeterminado_es_30_dias(): void
    {
        $respuesta = $this->indicadores()
            ->assertOk()
            ->assertViewIs('dashboard');

        $this->assertSame('30', $respuesta->viewData('periodo'));

        $this->assertSame([
            'total' => 3,
            'recibidos' => 1,
            'en_proceso' => 1,
            'resueltos' => 1,
            'tasa' => 33,
        ], $respuesta->viewData('kpis'));
    }

    public function test_filtros_de_7_90_dias_y_todo(): void
    {
        $siete = $this->indicadores('7')->assertOk();

        $this->assertSame(2, $siete->viewData('kpis')['total']);
        $this->assertSame(0, $siete->viewData('kpis')['resueltos']);

        $noventa = $this->indicadores('90')->assertOk();

        $this->assertSame(4, $noventa->viewData('kpis')['total']);
        $this->assertSame(25, $noventa->viewData('kpis')['tasa']);

        $todo = $this->indicadores('todo')->assertOk();

        $this->assertSame(5, $todo->viewData('kpis')['total']);
        $this->assertSame(2, $todo->viewData('kpis')['resueltos']);
        $this->assertSame(40, $todo->viewData('kpis')['tasa']);
    }

    public function test_periodo_invalido_se_reemplaza_por_30(): void
    {
        $respuesta = $this->indicadores('invalido')->assertOk();

        $this->assertSame('30', $respuesta->viewData('periodo'));
        $this->assertSame(3, $respuesta->viewData('kpis')['total']);
    }

    public function test_demorados_respetan_estado_y_periodo(): void
    {
        // Solo cuentan los recibidos con más de 3 días.
        $this->assertSame(
            1,
            $this->indicadores('7')->viewData('demorados')
        );

        $this->assertSame(
            1,
            $this->indicadores('30')->viewData('demorados')
        );

        $this->assertSame(
            2,
            $this->indicadores('90')->viewData('demorados')
        );

        $this->assertSame(
            2,
            $this->indicadores('todo')->viewData('demorados')
        );
    }

    public function test_promedio_con_precision_decimal(): void
    {
        $respuesta = $this->indicadores('30')->assertOk();

        // Un reclamo resuelto en 2 horas y 30 minutos.
        $this->assertSame([
            'valor' => '2,5',
            'unidad' => 'h',
        ], $respuesta->viewData('tiempo'));

        $todo = $this->indicadores('todo')->assertOk();

        // Promedio entre 2,5 y 49 horas = 25,75 horas.
        $this->assertSame([
            'valor' => '25,8',
            'unidad' => 'h',
        ], $todo->viewData('tiempo'));

        $sinResueltos = $this->indicadores('7')->assertOk();

        $this->assertSame([
            'valor' => '—',
            'unidad' => '',
        ], $sinResueltos->viewData('tiempo'));
    }

    public function test_promedio_mayor_a_48_horas_se_muestra_en_dias(): void
    {
        // Dejamos un solo reclamo resuelto dentro de 30 días,
        // con una duración de 72 horas.
        $reporte = DB::table('reportes')->where('id', 12)->first();

        DB::table('reportes')->where('id', 12)->update([
            'fecha_resolucion' => Carbon::parse(
                $reporte->fecha_creacion
            )->addHours(72)->format('Y-m-d H:i:s'),
        ]);

        $respuesta = $this->indicadores('30')->assertOk();

        $this->assertSame([
            'valor' => '3,0',
            'unidad' => 'días',
        ], $respuesta->viewData('tiempo'));
    }

    public function test_distribuciones_son_correctas(): void
    {
        $respuesta = $this->indicadores('30')->assertOk();

        $porCategoria = $respuesta->viewData('porCategoria')
            ->pluck('total', 'nombre');

        $this->assertSame(2, (int) $porCategoria['Alumbrado']);
        $this->assertSame(1, (int) $porCategoria['Residuos']);

        $porArea = $respuesta->viewData('porArea')
            ->pluck('total', 'nombre');

        $this->assertSame(2, (int) $porArea['Obras Públicas']);
        $this->assertSame(1, (int) $porArea['Higiene Urbana']);

        $porReferencia = $respuesta->viewData('porBarrio')
            ->pluck('total', 'nombre');

        $this->assertSame(2, (int) $porReferencia['Centro']);
        $this->assertSame(1, (int) $porReferencia['Norte']);
    }

    public function test_serie_y_recientes_son_independientes_del_periodo(): void
    {
        $respuesta = $this->indicadores('7')->assertOk();

        $serie = $respuesta->viewData('serie');

        // Siempre son 14 posiciones.
        $this->assertCount(14, $serie);

        // En los últimos 14 días se crearon 3 reclamos.
        $this->assertSame(
            3,
            array_sum(array_column($serie, 'total'))
        );

        // Los recientes no están limitados al período elegido.
        $recientes = $respuesta->viewData('recientes');

        $this->assertCount(5, $recientes);
        $this->assertSame(11, (int) $recientes->first()->id);
    }

    public function test_panel_funciona_sin_reclamos(): void
    {
        DB::table('reportes')->delete();

        $respuesta = $this->indicadores()->assertOk();

        $this->assertSame([
            'total' => 0,
            'recibidos' => 0,
            'en_proceso' => 0,
            'resueltos' => 0,
            'tasa' => 0,
        ], $respuesta->viewData('kpis'));

        $this->assertSame(0, $respuesta->viewData('demorados'));

        $this->assertSame([
            'valor' => '—',
            'unidad' => '',
        ], $respuesta->viewData('tiempo'));

        $this->assertCount(14, $respuesta->viewData('serie'));
        $this->assertCount(0, $respuesta->viewData('recientes'));
    }

    public function test_ciudadano_no_puede_acceder_a_indicadores(): void
    {
        $this->actingAs($this->usuario('ciudadano'))
            ->get('/indicadores')
            ->assertForbidden();
    }
}
