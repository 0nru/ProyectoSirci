<?php

namespace App\Http\Controllers;

use App\Support\Reclamos;
use App\Services\AgrupadorDeReclamos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

// Gestión de reclamos por parte del personal municipal (RF-13 a RF-17, RF-23).
// Solo entran operadores y supervisores (middleware SoloMunicipio).
class GestionController extends Controller
{
    // Listado de reclamos con filtros y paginación.
    public function index(Request $request)
    {
        $consulta = Reclamos::base();

        $estado = $request->query('estado');
        if (in_array($estado, Reclamos::ESTADOS, true)) {
            $consulta->where('e.nombre', $estado);
        }

        if ($request->filled('categoria')) {
            $consulta->where('r.categoria_id', (int) $request->query('categoria'));
        }

        if ($request->filled('area')) {
            $consulta->where('r.area_id', (int) $request->query('area'));
        }

        // Búsqueda por número de reclamo, barrio/dirección o texto de la descripción.
        $busqueda = trim((string) $request->query('q', ''));
        if ($busqueda !== '') {
            $numero = ltrim($busqueda, '#');

            $consulta->where(function ($w) use ($busqueda, $numero) {
                $w->where('r.barrio', 'like', '%' . $busqueda . '%')
                  ->orWhere('r.descripcion', 'like', '%' . $busqueda . '%');

                if (ctype_digit($numero)) {
                    $w->orWhere('r.id', (int) $numero);
                }
            });
        }

        $reclamos = $consulta
            ->orderByDesc('r.fecha_creacion')
            ->paginate(12)
            ->withQueryString();

        return view('gestion.index', [
            'reclamos'   => $reclamos,
            'categorias' => DB::table('categorias')->orderBy('nombre')->get(),
            'areas'      => DB::table('areas')->orderBy('nombre')->get(),
            'estados'    => Reclamos::ESTADOS,
            'filtros'    => [
                'estado'    => $estado,
                'categoria' => $request->query('categoria'),
                'area'      => $request->query('area'),
                'q'         => $busqueda,
            ],
        ]);
    }

    // Detalle de un reclamo con los controles de gestión.
    public function ver(AgrupadorDeReclamos $agrupador, int $reporte)
    {
        $reclamo = Reclamos::base()->where('r.id', $reporte)->first();
        abort_if($reclamo === null, 404);

        $ciudadano = DB::table('usuarios')
            ->where('id', $reclamo->usuario_id)
            ->first(['nombre', 'email', 'telefono']);

        // HU5: posibles incidencias repetidas cerca de este reclamo.
        // Solo se advierte al personal municipal; no se fusionan registros.
        $radioMetros = (float) config('sirci.radio_agrupacion_metros', 30);
        $coincidencias = collect();

        if ($reclamo->estado !== 'resuelto') {
            // Primera preselección en SQL: misma categoría y estados pendientes.
            $candidatos = Reclamos::base()
                ->where('r.categoria_id', $reclamo->categoria_id)
                ->where('r.id', '<>', $reporte)
                ->whereIn('e.nombre', ['recibido', 'en proceso'])
                ->get();

            // Distancia exacta Haversine en metros: reutiliza el servicio probado.
            $coincidencias = $candidatos
                ->map(function ($candidato) use ($agrupador, $reclamo) {
                    $candidato->distancia_metros = $agrupador->distanciaEnMetros(
                        (float) $reclamo->latitud,
                        (float) $reclamo->longitud,
                        (float) $candidato->latitud,
                        (float) $candidato->longitud
                    );

                    return $candidato;
                })
                ->filter(fn ($candidato) => $candidato->distancia_metros <= $radioMetros)
                ->sortBy('distancia_metros')
                ->values();
        }

        // Vinculaciones ya confirmadas: se muestran separadas de las sugerencias.
        $vinculoComoDuplicado = DB::table('reportes_duplicados')
            ->where('reporte_duplicado_id', $reporte)
            ->first();

        $duplicadosConfirmados = DB::table('reportes_duplicados as d')
            ->join('reportes as r', 'r.id', '=', 'd.reporte_duplicado_id')
            ->where('d.reporte_principal_id', $reporte)
            ->orderBy('d.fecha_confirmacion')
            ->orderBy('d.id')
            ->select('d.reporte_duplicado_id', 'd.fecha_confirmacion', 'd.observacion', 'r.barrio')
            ->get();

        // Usamos estas relaciones para impedir desde la interfaz las vinculaciones
        // circulares o que convertirían un reclamo principal en duplicado.
        $idsCandidatos = $coincidencias->pluck('id')->all();
        $vinculosCandidatos = empty($idsCandidatos)
            ? collect()
            : DB::table('reportes_duplicados')
                ->whereIn('reporte_duplicado_id', $idsCandidatos)
                ->get()
                ->keyBy('reporte_duplicado_id');
        $principalesCandidatos = empty($idsCandidatos)
            ? collect()
            : DB::table('reportes_duplicados')
                ->whereIn('reporte_principal_id', $idsCandidatos)
                ->pluck('reporte_principal_id');

        return view('gestion.show', [
            'reclamo'     => $reclamo,
            'ciudadano'   => $ciudadano,
            'historial'   => Reclamos::historial($reporte),
            'transiciones' => Reclamos::transiciones($reclamo->estado, Auth::user()->rol),
            'areas'       => DB::table('areas')->orderBy('nombre')->get(),
            'fotos'       => DB::table('fotos')->where('reporte_id', $reporte)->orderBy('orden')->get(),
            'municipio'   => true,
            'coincidencias' => $coincidencias,
            'radioMetros' => $radioMetros,
            'vinculoComoDuplicado' => $vinculoComoDuplicado,
            'duplicadosConfirmados' => $duplicadosConfirmados,
            'vinculosCandidatos' => $vinculosCandidatos,
            'principalesCandidatos' => $principalesCandidatos,
        ]);
    }

    // HU5: confirma una coincidencia geográfica sin fusionar los reclamos.
    // La relación siempre se orienta hacia el reclamo más antiguo.
    public function confirmarDuplicado(Request $request, AgrupadorDeReclamos $agrupador, int $reporte, int $similar)
    {
        $datos = $request->validate([
            'observacion_duplicado' => ['nullable', 'string', 'max:500'],
        ], [
            'observacion_duplicado.max' => 'La observación no puede superar los 500 caracteres.',
        ]);

        if ($reporte === $similar) {
            throw ValidationException::withMessages([
                'duplicado' => 'Un reclamo no puede ser duplicado de sí mismo.',
            ]);
        }

        $radioMetros = (float) config('sirci.radio_agrupacion_metros', 30);

        // Bloqueo y comprobación en una sola transacción: evita confirmaciones
        // concurrentes incompatibles y garantiza que el estado siga siendo válido.
        $resultado = DB::transaction(function () use ($reporte, $similar, $datos, $agrupador, $radioMetros) {
            $filas = DB::table('reportes')
                ->whereIn('id', [$reporte, $similar])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            abort_unless($filas->count() === 2, 404);

            $actual = $filas->get($reporte);
            $candidato = $filas->get($similar);

            if ((int) $actual->categoria_id !== (int) $candidato->categoria_id) {
                throw ValidationException::withMessages([
                    'duplicado' => 'Los reclamos deben pertenecer a la misma categoría.',
                ]);
            }

            $pendientes = DB::table('estados')
                ->whereIn('nombre', ['recibido', 'en proceso'])
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if (! in_array((int) $actual->estado_id, $pendientes, true)
                || ! in_array((int) $candidato->estado_id, $pendientes, true)) {
                throw ValidationException::withMessages([
                    'duplicado' => 'Solo se pueden vincular reclamos pendientes.',
                ]);
            }

            $distancia = $agrupador->distanciaEnMetros(
                (float) $actual->latitud,
                (float) $actual->longitud,
                (float) $candidato->latitud,
                (float) $candidato->longitud
            );

            if ($distancia > $radioMetros) {
                throw ValidationException::withMessages([
                    'duplicado' => 'Los reclamos ya no están dentro del radio permitido.',
                ]);
            }

            // El de menor fecha es principal; ante empate, el menor ID.
            $actualEsMasAntiguo = $actual->fecha_creacion < $candidato->fecha_creacion
                || ($actual->fecha_creacion === $candidato->fecha_creacion
                    && (int) $actual->id < (int) $candidato->id);

            $principalId = (int) ($actualEsMasAntiguo ? $actual->id : $candidato->id);
            $duplicadoId = (int) ($actualEsMasAntiguo ? $candidato->id : $actual->id);

            $vinculoExistente = DB::table('reportes_duplicados')
                ->where('reporte_duplicado_id', $duplicadoId)
                ->first();

            if ($vinculoExistente && (int) $vinculoExistente->reporte_principal_id === $principalId) {
                return 'Estos reclamos ya estaban vinculados.';
            }

            // Un reclamo puede tener varios duplicados, pero no ser a la vez
            // principal de un grupo y duplicado de otro: evita cadenas y ciclos.
            $incompatible = $vinculoExistente !== null
                || DB::table('reportes_duplicados')->where('reporte_duplicado_id', $principalId)->exists()
                || DB::table('reportes_duplicados')->where('reporte_principal_id', $duplicadoId)->exists();

            if ($incompatible) {
                throw ValidationException::withMessages([
                    'duplicado' => 'Uno de los reclamos ya pertenece a otro grupo de duplicados.',
                ]);
            }

            DB::table('reportes_duplicados')->insert([
                'reporte_principal_id' => $principalId,
                'reporte_duplicado_id' => $duplicadoId,
                'confirmado_por' => Auth::id(),
                'observacion' => $datos['observacion_duplicado'] ?? null,
                'fecha_confirmacion' => DB::raw('CURRENT_TIMESTAMP'),
            ]);

            return "Duplicado confirmado: reclamo #{$duplicadoId} vinculado al principal #{$principalId}.";
        });

        return redirect()->route('gestion.ver', ['reporte' => $reporte])->with('status', $resultado);
    }

    // Cambia el estado de un reclamo, lo deja en el historial y avisa al ciudadano.
    public function cambiarEstado(Request $request, int $reporte)
    {
        $datos = $request->validate([
            'estado'      => ['required', Rule::in(Reclamos::ESTADOS)],
            'observacion' => ['nullable', 'string', 'max:500'],
        ], [
            'estado.required' => 'Elegí el nuevo estado.',
            'estado.in'       => 'El estado elegido no es válido.',
        ]);

        $usuario = Auth::user();
        $nuevo = $datos['estado'];

        // Todo ocurre en una transacción: si algo falla, no se guarda nada.
        DB::transaction(function () use ($reporte, $datos, $usuario, $nuevo) {
            // Bloquea la fila para evitar que dos personas la cambien a la vez.
            $fila = DB::table('reportes')->where('id', $reporte)->lockForUpdate()->first();
            abort_if($fila === null, 404);

            $actual = DB::table('estados')->where('id', $fila->estado_id)->value('nombre');

            // Regla RN-02: solo se permiten ciertas transiciones.
            if (! in_array($nuevo, Reclamos::transiciones($actual, $usuario->rol), true)) {
                throw ValidationException::withMessages([
                    'estado' => 'No se puede pasar de «' . $actual . '» a «' . $nuevo . '».',
                ]);
            }

            $estadoId = DB::table('estados')->where('nombre', $nuevo)->value('id');

            DB::table('reportes')->where('id', $reporte)->update([
                'estado_id'        => $estadoId,
                'fecha_resolucion' => $nuevo === 'resuelto' ? DB::raw('CURRENT_TIMESTAMP') : null,
            ]);

            DB::table('historial_estados')->insert([
                'reporte_id'   => $reporte,
                'estado_id'    => $estadoId,
                'area_id'      => $fila->area_id,
                'usuario_id'   => $usuario->id,
                'observacion'  => $datos['observacion'] ?? null,
                'fecha_cambio' => DB::raw('CURRENT_TIMESTAMP'),
            ]);

            // Aviso al ciudadano dentro de la aplicación (RF-10).
            $categoria = DB::table('categorias')->where('id', $fila->categoria_id)->value('nombre');

            if ($nuevo === 'resuelto') {
                $mensaje = "Tu reclamo #{$reporte} ({$categoria}) fue resuelto.";
            } elseif ($actual === 'resuelto') {
                $mensaje = "Tu reclamo #{$reporte} ({$categoria}) fue reabierto y vuelve a estar en proceso.";
            } else {
                $mensaje = "Tu reclamo #{$reporte} ({$categoria}) ahora está «{$nuevo}».";
            }

            DB::table('notificaciones')->insert([
                'usuario_id' => $fila->usuario_id,
                'reporte_id' => $reporte,
                'mensaje'    => $mensaje,
                'leida'      => 0,
                'fecha'      => DB::raw('CURRENT_TIMESTAMP'),
            ]);
        });

        return redirect('/gestion/' . $reporte)
            ->with('status', 'Estado actualizado: el ciudadano ya recibió el aviso.');
    }

    // Asigna o reasigna el área responsable (RF-16).
    public function asignarArea(Request $request, int $reporte)
    {
        $datos = $request->validate([
            'area_id' => ['required', 'integer', Rule::exists('areas', 'id')],
        ], [
            'area_id.required' => 'Elegí un área.',
            'area_id.exists'   => 'El área elegida no es válida.',
        ]);

        $usuario = Auth::user();
        $mensaje = null;

        DB::transaction(function () use ($reporte, $datos, $usuario, &$mensaje) {
            $fila = DB::table('reportes')->where('id', $reporte)->lockForUpdate()->first();
            abort_if($fila === null, 404);

            if ((int) $fila->area_id === (int) $datos['area_id']) {
                $mensaje = 'El reclamo ya estaba asignado a esa área.';
                return;
            }

            $nombreArea = DB::table('areas')->where('id', $datos['area_id'])->value('nombre');

            DB::table('reportes')->where('id', $reporte)->update(['area_id' => $datos['area_id']]);

            DB::table('historial_estados')->insert([
                'reporte_id'   => $reporte,
                'estado_id'    => $fila->estado_id,
                'area_id'      => $datos['area_id'],
                'usuario_id'   => $usuario->id,
                'observacion'  => 'Reasignado al área: ' . $nombreArea,
                'fecha_cambio' => DB::raw('CURRENT_TIMESTAMP'),
            ]);

            $mensaje = 'Reclamo asignado a ' . $nombreArea . '.';
        });

        return redirect('/gestion/' . $reporte)->with('status', $mensaje);
    }
}
