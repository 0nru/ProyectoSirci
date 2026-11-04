<?php

namespace App\Http\Controllers;

use App\Support\Reclamos;
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
    public function ver(int $reporte)
    {
        $reclamo = Reclamos::base()->where('r.id', $reporte)->first();
        abort_if($reclamo === null, 404);

        $ciudadano = DB::table('usuarios')
            ->where('id', $reclamo->usuario_id)
            ->first(['nombre', 'email', 'telefono']);

        return view('gestion.show', [
            'reclamo'     => $reclamo,
            'ciudadano'   => $ciudadano,
            'historial'   => Reclamos::historial($reporte),
            'transiciones' => Reclamos::transiciones($reclamo->estado, Auth::user()->rol),
            'areas'       => DB::table('areas')->orderBy('nombre')->get(),
            'municipio'   => true,
        ]);
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
