<?php

namespace App\Http\Controllers;

use App\Support\Reclamos;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Pantalla de inicio después de iniciar sesión.
class PanelController extends Controller
{
    public function index()
    {
        $usuario = Auth::user();

        // El personal municipal inicia en Gestión de reclamos (recorrido acordado).
        if ($usuario->rol !== 'ciudadano') {
            return redirect()->route('gestion.index');
        }

        // Cantidad de reclamos propios por estado.
        $porEstado = DB::table('reportes as r')
            ->join('estados as e', 'e.id', '=', 'r.estado_id')
            ->where('r.usuario_id', $usuario->id)
            ->select('e.nombre', DB::raw('COUNT(*) as total'))
            ->groupBy('e.nombre')
            ->pluck('total', 'nombre');

        $resumen = [
            'total'     => (int) $porEstado->sum(),
            'activos'   => (int) ($porEstado['recibido'] ?? 0) + (int) ($porEstado['en proceso'] ?? 0),
            'resueltos' => (int) ($porEstado['resuelto'] ?? 0),
        ];

        // Últimos reclamos del ciudadano.
        $recientes = Reclamos::base()
            ->where('r.usuario_id', $usuario->id)
            ->orderByDesc('r.fecha_creacion')
            ->limit(4)
            ->get();

        // Reclamos activos en toda la ciudad (dato para el acceso al mapa).
        $activosCiudad = DB::table('reportes as r')
            ->join('estados as e', 'e.id', '=', 'r.estado_id')
            ->where('e.nombre', '<>', 'resuelto')
            ->count();

        return view('panel.ciudadano', compact('resumen', 'recientes', 'activosCiudad'));
    }
}
