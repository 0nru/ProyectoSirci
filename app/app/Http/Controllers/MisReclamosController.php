<?php

namespace App\Http\Controllers;

use App\Support\Reclamos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Seguimiento de los reclamos del ciudadano (RF-09, RF-11).
class MisReclamosController extends Controller
{
    // Listado propio, con filtro por estado.
    public function index(Request $request)
    {
        $usuario = Auth::user();

        if ($usuario->rol !== 'ciudadano') {
            return redirect('/gestion');
        }

        $filtro = $request->query('filtro');
        $consulta = Reclamos::base()->where('r.usuario_id', $usuario->id);

        if ($filtro === 'activos') {
            $consulta->where('e.nombre', '<>', 'resuelto');
        } elseif ($filtro === 'resueltos') {
            $consulta->where('e.nombre', 'resuelto');
        } else {
            $filtro = 'todos';
        }

        $reclamos = $consulta->orderByDesc('r.fecha_creacion')->get();

        // Cantidades para los filtros.
        $porEstado = DB::table('reportes as r')
            ->join('estados as e', 'e.id', '=', 'r.estado_id')
            ->where('r.usuario_id', $usuario->id)
            ->select('e.nombre', DB::raw('COUNT(*) as total'))
            ->groupBy('e.nombre')
            ->pluck('total', 'nombre');

        $conteos = [
            'todos'     => (int) $porEstado->sum(),
            'activos'   => (int) ($porEstado['recibido'] ?? 0) + (int) ($porEstado['en proceso'] ?? 0),
            'resueltos' => (int) ($porEstado['resuelto'] ?? 0),
        ];

        return view('reportes.index', compact('reclamos', 'filtro', 'conteos'));
    }

    // Detalle de un reclamo propio con su línea de tiempo.
    public function ver(int $reporte)
    {
        $usuario = Auth::user();

        if ($usuario->rol !== 'ciudadano') {
            return redirect('/gestion/' . $reporte);
        }

        $reclamo = Reclamos::base()
            ->where('r.id', $reporte)
            ->where('r.usuario_id', $usuario->id)
            ->first();

        abort_if($reclamo === null, 404);

        $historial = Reclamos::historial($reporte);

        return view('reportes.show', [
            'reclamo'   => $reclamo,
            'historial' => $historial,
            'fotos'     => DB::table('fotos')->where('reporte_id', $reporte)->orderBy('orden')->get(),
            'municipio' => false,
        ]);
    }
}
