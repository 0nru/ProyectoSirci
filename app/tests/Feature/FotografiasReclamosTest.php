<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FotografiasReclamosTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Nunca preparar tablas en la base MariaDB real.
        if (
            DB::connection()->getDriverName() !== 'sqlite' ||
            DB::connection()->getDatabaseName() !== ':memory:'
        ) {
            throw new \RuntimeException(
                'Estas pruebas requieren SQLite en memoria.'
            );
        }

        // Almacenamiento falso: no toca las fotos reales.
        Storage::fake('public');

        Schema::dropIfExists('fotos');
        Schema::dropIfExists('historial_estados');
        Schema::dropIfExists('reportes');
        Schema::dropIfExists('estados');
        Schema::dropIfExists('categorias');

        Schema::create('categorias', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre');
            $table->boolean('activa');
            $table->unsignedInteger('area_id_defecto');
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
            $table->string('descripcion', 500);
            $table->string('barrio', 80)->nullable();
            $table->decimal('latitud', 9, 6);
            $table->decimal('longitud', 9, 6);
            $table->dateTime('fecha_creacion')->useCurrent();
            $table->dateTime('fecha_resolucion')->nullable();
            $table->timestamps();
        });

        Schema::create('historial_estados', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('reporte_id');
            $table->unsignedInteger('estado_id');
            $table->unsignedInteger('area_id');
            $table->unsignedInteger('usuario_id');
            $table->string('observacion')->nullable();
            $table->dateTime('fecha_cambio')->useCurrent();
        });

        Schema::create('fotos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('reporte_id');
            $table->string('url');
            $table->unsignedInteger('orden');
            $table->unique(['reporte_id', 'orden']);
        });

        DB::table('categorias')->insert([
            [
                'id' => 1,
                'nombre' => 'Alumbrado público',
                'activa' => 1,
                'area_id_defecto' => 1,
            ],
            [
                'id' => 2,
                'nombre' => 'Categoría inactiva',
                'activa' => 0,
                'area_id_defecto' => 1,
            ],
        ]);

        DB::table('estados')->insert([
            'id' => 1,
            'nombre' => 'recibido',
        ]);
    }

    private function usuario(string $rol = 'ciudadano'): User
    {
        $usuario = new User();

        $usuario->forceFill([
            'id' => 100,
            'nombre' => 'Usuario de prueba',
            'email' => 'fotos@sirci.test',
            'rol' => $rol,
            'activo' => true,
        ]);

        return $usuario;
    }

    // PNG real de 1 x 1 píxel. No requiere la extensión GD.
    private function contenidoPng(): string
    {
        return base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwC'
            . 'AAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true
        );
    }

    private function fotoValida(string $nombre = 'foto.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $nombre,
            $this->contenidoPng()
        );
    }

    private function datosValidos(): array
    {
        return [
            'categoria_id' => 1,
            'descripcion' => 'Luminaria sin funcionamiento en la calle',
            'direccion' => 'Calle de prueba 123',
            'latitud' => -26.184900,
            'longitud' => -58.173100,
            'fotos' => [$this->fotoValida()],
        ];
    }

    private function comprobarSinRegistros(): void
    {
        $this->assertDatabaseCount('reportes', 0);
        $this->assertDatabaseCount('historial_estados', 0);
        $this->assertDatabaseCount('fotos', 0);

        $this->assertSame(
            [],
            Storage::disk('public')->allFiles('reportes')
        );
    }

    public function test_crea_reclamo_con_una_fotografia(): void
    {
        $this->actingAs($this->usuario())
            ->post('/reclamos', $this->datosValidos())
            ->assertRedirect('/panel');

        $this->assertDatabaseCount('reportes', 1);
        $this->assertDatabaseCount('historial_estados', 1);
        $this->assertDatabaseCount('fotos', 1);

        $reporte = DB::table('reportes')->first();

        $this->assertSame(100, (int) $reporte->usuario_id);
        $this->assertSame(1, (int) $reporte->estado_id);

        $this->assertDatabaseHas('historial_estados', [
            'reporte_id' => $reporte->id,
            'estado_id' => 1,
        ]);

        $foto = DB::table('fotos')->first();

        $this->assertSame(1, (int) $foto->orden);
        $this->assertStringStartsWith(
            'reportes/' . $reporte->id . '/',
            $foto->url
        );

        Storage::disk('public')->assertExists($foto->url);
    }

    public function test_permite_cuatro_fotografias(): void
    {
        $datos = $this->datosValidos();

        $datos['fotos'] = [];

        for ($i = 1; $i <= 4; $i++) {
            $datos['fotos'][] = $this->fotoValida(
                'foto' . $i . '.png'
            );
        }

        $this->actingAs($this->usuario())
            ->post('/reclamos', $datos)
            ->assertRedirect('/panel');

        $this->assertDatabaseCount('reportes', 1);
        $this->assertDatabaseCount('fotos', 4);

        $fotos = DB::table('fotos')
            ->orderBy('orden')
            ->get();

        foreach ($fotos as $indice => $foto) {
            $this->assertSame(
                $indice + 1,
                (int) $foto->orden
            );

            Storage::disk('public')->assertExists($foto->url);
        }
    }

    public function test_rechaza_cinco_fotografias(): void
    {
        $datos = $this->datosValidos();
        $datos['fotos'] = [];

        for ($i = 1; $i <= 5; $i++) {
            $datos['fotos'][] = $this->fotoValida(
                'foto' . $i . '.png'
            );
        }

        $this->actingAs($this->usuario())
            ->post('/reclamos', $datos)
            ->assertSessionHasErrors('fotos');

        $this->comprobarSinRegistros();
    }

    public function test_rechaza_reclamo_sin_fotografias(): void
    {
        $datos = $this->datosValidos();
        $datos['fotos'] = [];

        $this->actingAs($this->usuario())
            ->post('/reclamos', $datos)
            ->assertSessionHasErrors('fotos');

        $this->comprobarSinRegistros();
    }

    public function test_rechaza_archivo_que_no_es_imagen(): void
    {
        $datos = $this->datosValidos();

        $datos['fotos'] = [
            UploadedFile::fake()->createWithContent(
                'documento.txt',
                'Este archivo no es una fotografia.'
            ),
        ];

        $this->actingAs($this->usuario())
            ->post('/reclamos', $datos)
            ->assertSessionHasErrors('fotos.0');

        $this->comprobarSinRegistros();
    }

    public function test_rechaza_imagen_mayor_a_dos_megabytes(): void
    {
        $datos = $this->datosValidos();

        // PNG válido con contenido adicional para superar 2 MiB.
        $contenido = $this->contenidoPng()
            . str_repeat('X', 2 * 1024 * 1024);

        $datos['fotos'] = [
            UploadedFile::fake()->createWithContent(
                'imagen-grande.png',
                $contenido
            ),
        ];

        $this->actingAs($this->usuario())
            ->post('/reclamos', $datos)
            ->assertSessionHasErrors('fotos.0');

        $this->comprobarSinRegistros();
    }

    public function test_rechaza_categoria_inactiva(): void
    {
        $datos = $this->datosValidos();
        $datos['categoria_id'] = 2;

        $this->actingAs($this->usuario())
            ->post('/reclamos', $datos)
            ->assertSessionHasErrors('categoria_id');

        $this->comprobarSinRegistros();
    }

    public function test_operador_no_puede_crear_reclamos(): void
    {
        $this->actingAs($this->usuario('operador'))
            ->post('/reclamos', $this->datosValidos())
            ->assertForbidden();

        $this->comprobarSinRegistros();
    }
}
