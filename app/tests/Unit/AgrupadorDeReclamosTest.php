<?php

namespace Tests\Unit;

use App\Models\Estado;
use App\Models\Reporte;
use App\Services\AgrupadorDeReclamos;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

/*
 * Pruebas de la agrupación geográfica de reclamos.
 * Los reclamos se crean EN MEMORIA: no se guardan ni se lee la base de datos.
 */
class AgrupadorDeReclamosTest extends TestCase
{
    // Punto de partida de las pruebas (zona de Formosa capital).
    private const LATITUD_BASE = -26.1849;
    private const LONGITUD_BASE = -58.1731;

    // Metros que equivalen a un grado de latitud con el radio terrestre usado.
    private const METROS_POR_GRADO = 6371000 * M_PI / 180;

    // Latitud de un punto ubicado al norte de la base, a los metros indicados.
    // Al moverse solo hacia el norte, la distancia es exacta y fácil de razonar.
    private function latitudAlNorte(float $metros): float
    {
        return self::LATITUD_BASE + $metros / self::METROS_POR_GRADO;
    }

    // Crea un reclamo en memoria con el estado indicado.
    // Se usa forceFill porque "id" y "fecha_creacion" no están en $fillable.
    private function crearReclamo(
        int $id,
        int $categoriaId,
        float $latitud,
        string $estado = 'recibido',
        string $fecha = '2026-10-01 10:00:00'
    ): Reporte {
        $reclamo = new Reporte();
        $reclamo->forceFill([
            'id' => $id,
            'categoria_id' => $categoriaId,
            'latitud' => $latitud,
            'longitud' => self::LONGITUD_BASE,
            'fecha_creacion' => $fecha,
        ]);

        $modeloEstado = new Estado();
        $modeloEstado->forceFill(['nombre' => $estado]);

        // Se asigna la relación a mano para que no consulte la base.
        $reclamo->setRelation('estado', $modeloEstado);

        return $reclamo;
    }

    public function test_dos_reclamos_a_20_metros_se_agrupan(): void
    {
        $a = $this->crearReclamo(1, 1, self::LATITUD_BASE);
        $b = $this->crearReclamo(2, 1, $this->latitudAlNorte(20));

        $grupos = (new AgrupadorDeReclamos())->agrupar(collect([$a, $b]), 30);

        $this->assertCount(1, $grupos);
        $this->assertCount(2, $grupos[0]['reclamos']);
        $this->assertSame($a, $grupos[0]['referencia']);
    }

    public function test_dos_reclamos_a_40_metros_no_se_agrupan_y_el_radio_es_un_parametro(): void
    {
        $a = $this->crearReclamo(1, 1, self::LATITUD_BASE);
        $b = $this->crearReclamo(2, 1, $this->latitudAlNorte(40));
        $agrupador = new AgrupadorDeReclamos();

        // Con radio de 30 m quedan separados.
        $this->assertCount(2, $agrupador->agrupar(collect([$a, $b]), 30));

        // Con radio de 50 m, la misma pareja se agrupa: el radio no está fijo en el código.
        $this->assertCount(1, $agrupador->agrupar(collect([$a, $b]), 50));
    }

    public function test_no_hay_agrupacion_encadenada_a_b_c(): void
    {
        // A y B están a 25 m, B y C a 25 m, pero A y C a 50 m.
        $a = $this->crearReclamo(1, 1, self::LATITUD_BASE);
        $b = $this->crearReclamo(2, 1, $this->latitudAlNorte(25));
        $c = $this->crearReclamo(3, 1, $this->latitudAlNorte(50));

        $agrupador = new AgrupadorDeReclamos();

        // Comprueba que los datos de la prueba están bien armados.
        $this->assertEqualsWithDelta(
            50,
            $agrupador->distanciaEnMetros(self::LATITUD_BASE, self::LONGITUD_BASE, $this->latitudAlNorte(50), self::LONGITUD_BASE),
            0.01
        );

        $grupos = $agrupador->agrupar(collect([$a, $b, $c]), 30);

        // B se une a A, pero C no entra "a través" de B: forma otro grupo.
        $this->assertCount(2, $grupos);
        $this->assertSame($a, $grupos[0]['referencia']);
        $this->assertCount(2, $grupos[0]['reclamos']);
        $this->assertSame($c, $grupos[1]['referencia']);
        $this->assertCount(1, $grupos[1]['reclamos']);
    }

    public function test_categorias_diferentes_no_se_mezclan_aunque_tengan_las_mismas_coordenadas(): void
    {
        $a = $this->crearReclamo(1, 1, self::LATITUD_BASE);
        $b = $this->crearReclamo(2, 2, self::LATITUD_BASE);

        $grupos = (new AgrupadorDeReclamos())->agrupar(collect([$a, $b]), 30);

        $this->assertCount(2, $grupos);
        $this->assertCount(1, $grupos[0]['reclamos']);
        $this->assertCount(1, $grupos[1]['reclamos']);
    }

    public function test_los_reclamos_resueltos_no_se_incluyen(): void
    {
        $pendiente = $this->crearReclamo(1, 1, self::LATITUD_BASE, 'recibido');
        $resuelto = $this->crearReclamo(2, 1, self::LATITUD_BASE, 'resuelto');
        $agrupador = new AgrupadorDeReclamos();

        $grupos = $agrupador->agrupar(collect([$pendiente, $resuelto]), 30);
        $this->assertCount(1, $grupos);
        $this->assertCount(1, $grupos[0]['reclamos']);

        // Si todos están resueltos, no queda ningún grupo.
        $this->assertCount(0, $agrupador->agrupar(collect([$resuelto]), 30));
    }

    public function test_se_incluyen_los_estados_recibido_y_en_proceso(): void
    {
        $recibido = $this->crearReclamo(1, 1, self::LATITUD_BASE, 'recibido');
        $enProceso = $this->crearReclamo(2, 1, self::LATITUD_BASE, 'en proceso');

        $grupos = (new AgrupadorDeReclamos())->agrupar(collect([$recibido, $enProceso]), 30);

        $this->assertCount(1, $grupos);
        $this->assertCount(2, $grupos[0]['reclamos']);
    }

    public function test_un_reclamo_a_exactamente_30_metros_se_incorpora(): void
    {
        $a = $this->crearReclamo(1, 1, self::LATITUD_BASE);
        $b = $this->crearReclamo(2, 1, $this->latitudAlNorte(30));

        $grupos = (new AgrupadorDeReclamos())->agrupar(collect([$a, $b]), 30);

        $this->assertCount(1, $grupos);
        $this->assertCount(2, $grupos[0]['reclamos']);
    }

    public function test_la_referencia_es_el_reclamo_mas_antiguo_aunque_lleguen_desordenados(): void
    {
        $nuevo = $this->crearReclamo(1, 1, $this->latitudAlNorte(10), 'recibido', '2026-10-05 09:00:00');
        $antiguo = $this->crearReclamo(2, 1, self::LATITUD_BASE, 'recibido', '2026-10-01 09:00:00');

        // Se entregan primero el nuevo y después el antiguo, a propósito.
        $grupos = (new AgrupadorDeReclamos())->agrupar(collect([$nuevo, $antiguo]), 30);

        $this->assertCount(1, $grupos);
        $this->assertSame($antiguo, $grupos[0]['referencia']);
    }

    public function test_falla_con_un_aviso_claro_si_no_se_cargo_la_relacion_estado(): void
    {
        $sinEstado = new Reporte();
        $sinEstado->forceFill([
            'id' => 1,
            'categoria_id' => 1,
            'latitud' => self::LATITUD_BASE,
            'longitud' => self::LONGITUD_BASE,
            'fecha_creacion' => '2026-10-01 10:00:00',
        ]);

        $this->expectException(LogicException::class);

        (new AgrupadorDeReclamos())->agrupar(collect([$sinEstado]), 30);
    }

    public function test_rechaza_un_radio_negativo(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new AgrupadorDeReclamos())->agrupar(collect(), -1);
    }

    public function test_haversine_mide_un_grado_de_latitud_cerca_de_111_kilometros(): void
    {
        $metros = (new AgrupadorDeReclamos())->distanciaEnMetros(0, 0, 1, 0);

        $this->assertEqualsWithDelta(111194.93, $metros, 1.0);
    }
}