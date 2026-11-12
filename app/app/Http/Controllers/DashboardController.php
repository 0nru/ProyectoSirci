<?php

namespace App\Http\Controllers;

use App\Support\Reclamos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Tablero de indicadores para el personal municipal (RF-18).
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Período del análisis: últimos 7, 30 o 90 días, o todo el historial.
        $periodo = (string) $request->query('periodo', '30');
        if (! in_array($periodo, ['7', '30', '90', 'todo'], true)) {
            $periodo = '30';
        }
        $desde = $periodo === 'todo' ? null : now()->subDays((int) $periodo)->startOfDay();

        // Consulta de reclamos dentro del período (se vuelve a crear en cada uso).
        $enPeriodo = function () use ($desde) {
            $q = DB::table('reportes as r')->join('estados as e', 'e.id', '=', 'r.estado_id');
            if ($desde) {
                $q->where('r.fecha_creacion', '>=', $desde);
            }
            return $q;
        };

        // ---- Totales por estado ----
        $porEstado = $enPeriodo()
            ->select('e.nombre', DB::raw('COUNT(*) as total'))
            ->groupBy('e.nombre')
            ->pluck('total', 'nombre');

        $total = (int) $porEstado->sum();
        $kpis = [
            'total'      => $total,
            'recibidos'  => (int) ($porEstado['recibido'] ?? 0),
            'en_proceso' => (int) ($porEstado['en proceso'] ?? 0),
            'resueltos'  => (int) ($porEstado['resuelto'] ?? 0),
        ];
        $kpis['tasa'] = $total > 0 ? (int) round($kpis['resueltos'] * 100 / $total) : 0;

        // ---- Tiempo promedio de resolución ----
        // MariaDB y SQLite utilizan expresiones de fecha diferentes.
        $expresionTiempo = DB::connection()->getDriverName() === 'sqlite'
            ? "(strftime('%s', r.fecha_resolucion) - strftime('%s', r.fecha_creacion)) / 3600.0"
            : 'TIMESTAMPDIFF(SECOND, r.fecha_creacion, r.fecha_resolucion) / 3600.0';

        $horas = DB::table('reportes as r')
            ->whereNotNull('r.fecha_resolucion')
            ->when($desde, fn ($q) => $q->where('r.fecha_creacion', '>=', $desde))
            ->avg(DB::raw($expresionTiempo));

        if ($horas === null) {
            $tiempo = ['valor' => '—', 'unidad' => ''];
        } elseif ($horas < 48) {
            $tiempo = ['valor' => number_format((float) $horas, 1, ',', '.'), 'unidad' => 'h'];
        } else {
            $tiempo = ['valor' => number_format($horas / 24, 1, ',', '.'), 'unidad' => 'días'];
        }
        // Reclamos recibidos hace más de 3 días,
        // respetando el período seleccionado.
        $demorados = DB::table('reportes as r')
            ->join('estados as e', 'e.id', '=', 'r.estado_id')
            ->where('e.nombre', 'recibido')
            ->where('r.fecha_creacion', '<', now()->subDays(3))
            ->when($desde, function ($consulta) use ($desde) {
                $consulta->where('r.fecha_creacion', '>=', $desde);
            })

            ->count();

        // ---- Distribuciones ----
        $porCategoria = $enPeriodo()
            ->join('categorias as c', 'c.id', '=', 'r.categoria_id')
            ->select('c.nombre', DB::raw('COUNT(*) as total'))
            ->groupBy('c.nombre')
            ->orderByDesc('total')
            ->get();

        $porBarrio = $enPeriodo()
            ->whereNotNull('r.barrio')
            ->where('r.barrio', '<>', '')
            ->select('r.barrio as nombre', DB::raw('COUNT(*) as total'))
            ->groupBy('r.barrio')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $porArea = $enPeriodo()
            ->join('areas as a', 'a.id', '=', 'r.area_id')
            ->select('a.nombre', DB::raw('COUNT(*) as total'))
            ->groupBy('a.nombre')
            ->orderByDesc('total')
            ->get();

        // ---- Reclamos nuevos por día (últimos 14 días) ----
        $inicioSerie = now()->subDays(13)->startOfDay();
        $porDia = DB::table('reportes')
            ->where('fecha_creacion', '>=', $inicioSerie)
            ->select(DB::raw('DATE(fecha_creacion) as dia'), DB::raw('COUNT(*) as total'))
            ->groupBy('dia')
            ->pluck('total', 'dia');

        $serie = [];
        for ($i = 13; $i >= 0; $i--) {
            $fecha = now()->subDays($i);
            $serie[] = [
                'etiqueta' => $fecha->format('j'),
                'completa' => $fecha->translatedFormat('j M'),
                'total'    => (int) ($porDia[$fecha->toDateString()] ?? 0),
            ];
        }

        // ---- Últimos reclamos recibidos ----
        $recientes = Reclamos::base()->orderByDesc('r.fecha_creacion')->limit(6)->get();

        return view('dashboard', [
            'periodo'      => $periodo,
            'kpis'         => $kpis,
            'tiempo'       => $tiempo,
            'demorados'    => $demorados,
            'porEstado'    => $porEstado,
            'porCategoria' => $porCategoria,
            'porBarrio'    => $porBarrio,
            'porArea'      => $porArea,
            'serie'        => $serie,
            'recientes'    => $recientes,
        ]);
    }
}
