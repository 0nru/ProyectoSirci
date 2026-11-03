<?php

namespace App\Http\Controllers\Supervisor;

// Controlador base de Laravel.
use App\Http\Controllers\Controller;

// Modelos que necesitamos consultar.
use App\Models\Reporte;

// Servicio de agrupamiento geográfico que ya probamos.
use App\Services\AgrupadorDeReclamos;

// Permite acceder al usuario que inició sesión.
use Illuminate\Support\Facades\Auth;

// Controlador de ubicaciones del panel del supervisor.
class UbicacionController extends Controller
{
    /**
     * Muestra las ubicaciones agrupadas en el panel del supervisor.
     */
    public function index(AgrupadorDeReclamos $agrupador)
    {
        $this->soloSupervisor();

        // La consulta y el agrupamiento están en un método compartido
        // para que la lista y el detalle usen exactamente el mismo proceso.
        [$grupos, $radioMetros] = $this->obtenerGrupos($agrupador);

        return view('supervisor.ubicaciones', [
            'grupos' => $grupos,
            'radioMetros' => $radioMetros,
        ]);
    }

    /**
     * Muestra los reclamos individuales de una ubicación.
     * $referencia es el ID del reclamo que funciona como punto de referencia fijo.
     */
    public function show(AgrupadorDeReclamos $agrupador, int $referencia)
    {
        $this->soloSupervisor();

        // Se recalculan los grupos con las mismas reglas que la lista.
        [$grupos, $radioMetros] = $this->obtenerGrupos($agrupador);

        // El ID de la URL solo sirve para ELEGIR entre los grupos ya calculados.
        // Nunca se busca un reclamo directamente por ese ID: así no se pueden
        // mostrar reclamos resueltos ni mezclar categorías.
        $grupo = $grupos->first(
            fn ($grupo) => (int) $grupo['referencia']->id === $referencia
        );

        // Si el grupo ya no existe (por ejemplo, su reclamo de referencia se
        // resolvió), se vuelve a la lista con un mensaje explicativo.
        if ($grupo === null) {
            return redirect()
                ->route('supervisor.ubicaciones')
                ->with('aviso', 'Esa ubicación ya no existe o cambió. Elegí una de la lista.');
        }

        // Distancia de cada reclamo respecto de la referencia.
        // Reutiliza el cálculo del servicio: no se repite la fórmula.
        $referenciaDelGrupo = $grupo['referencia'];

        $distancias = $grupo['reclamos']->mapWithKeys(fn ($reclamo) => [
            $reclamo->id => $agrupador->distanciaEnMetros(
                (float) $referenciaDelGrupo->latitud,
                (float) $referenciaDelGrupo->longitud,
                (float) $reclamo->latitud,
                (float) $reclamo->longitud
            ),
        ]);

        return view('supervisor.ubicacion-detalle', [
            'grupo' => $grupo,
            'distancias' => $distancias,
            'radioMetros' => $radioMetros,
        ]);
    }

    /**
     * Solo el rol supervisor puede usar estas pantallas.
     */
    private function soloSupervisor(): void
    {
        abort_unless(
            Auth::user()->rol === 'supervisor',
            403,
            'No tenés autorización para consultar estas ubicaciones.'
        );
    }

    /**
     * Consulta los reclamos pendientes, los agrupa y prepara el resumen de cada grupo.
     * Devuelve [grupos, radio en metros].
     */
    private function obtenerGrupos(AgrupadorDeReclamos $agrupador): array
    {
        // Obtiene el radio desde la configuración (30 metros por defecto).
        $radioMetros = (float) config('sirci.radio_agrupamiento_metros', 30);

        // Consulta los reclamos y carga sus relaciones.
        // Es importante cargar "estado": el agrupador lo exige.
        $reclamos = Reporte::with(['estado', 'categoria'])
            ->whereHas('estado', function ($consulta) {
                $consulta->whereIn('nombre', ['recibido', 'en proceso']);
            })
            ->orderBy('fecha_creacion')
            ->orderBy('id')
            ->get();

        // Agrupa los reclamos según las reglas geográficas.
        $grupos = $agrupador->agrupar($reclamos, $radioMetros);

        // Prepara un resumen de cada grupo para las vistas.
        $grupos = $grupos->map(function ($grupo) {
            $reclamosDelGrupo = $grupo['reclamos'];

            // Fecha del reclamo más reciente dentro del grupo.
            $grupo['fecha_mas_reciente'] = $reclamosDelGrupo
                ->max('fecha_creacion');

            // Cantidad de reclamos por estado.
            $grupo['resumen_estados'] = $reclamosDelGrupo
                ->groupBy(fn ($reclamo) => $reclamo->estado?->nombre ?? 'sin estado')
                ->map(fn ($reclamosEstado) => $reclamosEstado->count());

            return $grupo;
        });

        return [$grupos, $radioMetros];
    }
}