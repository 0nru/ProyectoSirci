<?php

namespace App\Services;

// Modelo que representa un reclamo.
use App\Models\Reporte;

// Colección de Laravel: lista con métodos útiles (filter, sort, map...).
use Illuminate\Support\Collection;

// Excepciones para avisar de usos incorrectos de esta clase.
use InvalidArgumentException;
use LogicException;

/*
 * Agrupa reclamos cercanos en "ubicaciones".
 *
 * Esta clase NO consulta la base de datos: recibe reclamos ya cargados y
 * devuelve los grupos. Por eso se puede probar sin base y reutilizar desde
 * cualquier controlador.
 *
 * Reglas:
 *  - Solo se consideran reclamos pendientes ("recibido" y "en proceso").
 *  - Solo se agrupan reclamos de la misma categoría.
 *  - El primer reclamo (el más antiguo) de cada grupo es su punto de
 *    referencia y NUNCA se mueve.
 *  - Un reclamo se compara únicamente con los puntos de referencia, jamás con
 *    otros miembros del grupo. Así no puede haber agrupación encadenada.
 */
class AgrupadorDeReclamos
{
    // Radio medio de la Tierra, en metros, usado por la fórmula de Haversine.
    private const RADIO_TIERRA_METROS = 6371000.0;

    // Margen de 1 milímetro para absorber errores de redondeo de los decimales
    // del computador. Sin él, un reclamo a "exactamente 30 m" podría medirse
    // como 30,0000000001 m y quedar afuera. Las coordenadas de la base tienen
    // una precisión de unos 10 cm, así que 1 mm no cambia ningún resultado real.
    private const TOLERANCIA_METROS = 0.001;

    // Estados que se consideran pendientes de atención.
    private const ESTADOS_PENDIENTES = ['recibido', 'en proceso'];

    /*
     * Agrupa los reclamos pendientes por cercanía.
     *
     * IMPORTANTE: cada reclamo debe traer cargada la relación "estado"
     * (por ejemplo, Reporte::with('estado')->get()).
     *
     * Devuelve una colección de grupos, ordenados por antigüedad de su
     * reclamo de referencia. Cada grupo es un arreglo con:
     *   'categoria_id' => id de la categoría del grupo
     *   'referencia'   => el Reporte que es el punto de referencia fijo
     *   'reclamos'     => colección de Reporte del grupo (incluye la referencia)
     */
    public function agrupar(Collection $reclamos, float $radioMetros): Collection
    {
        if ($radioMetros < 0) {
            throw new InvalidArgumentException('El radio de agrupación no puede ser negativo.');
        }

        // 1. Se descartan los reclamos resueltos y se ordenan de forma fija:
        //    primero por fecha de creación y, si empatan, por ID.
        //    Con este orden el resultado es siempre el mismo.
        $pendientes = $reclamos
            ->filter(fn (Reporte $reclamo) => $this->estaPendiente($reclamo))
            ->sort(fn (Reporte $a, Reporte $b) =>
                [$this->marcaDeTiempo($a), $a->id] <=> [$this->marcaDeTiempo($b), $b->id]
            )
            ->values();

        // Lista de grupos, y para cada categoría, las posiciones de sus grupos.
        $grupos = [];
        $posicionesPorCategoria = [];

        foreach ($pendientes as $reclamo) {
            $categoriaId = $reclamo->categoria_id;

            $grupoElegido = null;
            $distanciaElegida = null;

            // 2. Se compara solo con los puntos de referencia de los grupos
            //    existentes de la MISMA categoría.
            foreach ($posicionesPorCategoria[$categoriaId] ?? [] as $posicion) {
                $referencia = $grupos[$posicion]['referencia'];

                $distancia = $this->distanciaEnMetros(
                    (float) $referencia->latitud,
                    (float) $referencia->longitud,
                    (float) $reclamo->latitud,
                    (float) $reclamo->longitud
                );

                // Fuera del radio: este grupo no sirve.
                if ($distancia > $radioMetros + self::TOLERANCIA_METROS) {
                    continue;
                }

                // Entre varios grupos dentro del radio se elige el más cercano.
                // Si empatan, se queda el primero encontrado (el más antiguo).
                if ($distanciaElegida === null || $distancia < $distanciaElegida) {
                    $grupoElegido = $posicion;
                    $distanciaElegida = $distancia;
                }
            }

            if ($grupoElegido === null) {
                // 3a. Ningún grupo cercano: este reclamo crea uno nuevo
                //     y pasa a ser su punto de referencia fijo.
                $grupos[] = [
                    'categoria_id' => $categoriaId,
                    'referencia' => $reclamo,
                    'reclamos' => [$reclamo],
                ];
                $posicionesPorCategoria[$categoriaId][] = array_key_last($grupos);
            } else {
                // 3b. Se suma al grupo más cercano. La referencia no se toca.
                $grupos[$grupoElegido]['reclamos'][] = $reclamo;
            }
        }

        // Los reclamos de cada grupo se devuelven como colección.
        return collect($grupos)->map(fn (array $grupo) => [
            'categoria_id' => $grupo['categoria_id'],
            'referencia' => $grupo['referencia'],
            'reclamos' => collect($grupo['reclamos']),
        ])->values();
    }

    /*
     * Distancia en metros entre dos puntos (fórmula de Haversine).
     * Es pública para poder comprobarla de forma directa en las pruebas.
     */
    public function distanciaEnMetros(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $phi1 = deg2rad($lat1);
        $phi2 = deg2rad($lat2);
        $deltaPhi = deg2rad($lat2 - $lat1);
        $deltaLambda = deg2rad($lon2 - $lon1);

        $a = sin($deltaPhi / 2) ** 2
            + cos($phi1) * cos($phi2) * sin($deltaLambda / 2) ** 2;

        return self::RADIO_TIERRA_METROS * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    // Indica si el reclamo está recibido o en proceso.
    private function estaPendiente(Reporte $reclamo): bool
    {
        // Si la relación no está cargada, Laravel haría una consulta a la base
        // por cada reclamo. Se prefiere detener el proceso con un aviso claro.
        if (! $reclamo->relationLoaded('estado')) {
            throw new LogicException(
                'Cargá la relación "estado" antes de agrupar, por ejemplo con Reporte::with(\'estado\').'
            );
        }

        return in_array($reclamo->estado?->nombre, self::ESTADOS_PENDIENTES, true);
    }

    // Fecha de creación como número, para poder ordenar. Si falta, vale 0.
    private function marcaDeTiempo(Reporte $reclamo): int
    {
        return $reclamo->fecha_creacion?->getTimestamp() ?? 0;
    }
}