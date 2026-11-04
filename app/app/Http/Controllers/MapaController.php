<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Support\Fecha;
use App\Support\Reclamos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Mapa público de reclamos (RF-08). No expone datos personales (RN-08).
class MapaController extends Controller
{
    // Página del mapa.
    public function index()
    {
        $categorias = Categoria::orderBy('nombre')->get();

        return view('mapa', [
            'categorias' => $categorias,
            'estados'    => Reclamos::ESTADOS,
        ]);
    }

    // Datos del mapa en formato JSON, con filtros opcionales.
    public function datos(Request $request)
    {
        $consulta = DB::table('reportes as r')
            ->join('categorias as c', 'c.id', '=', 'r.categoria_id')
            ->join('estados as e', 'e.id', '=', 'r.estado_id')
            ->select(
                'r.id',
                'c.nombre as categoria',
                'e.nombre as estado',
                'r.latitud',
                'r.longitud',
                'r.barrio',
                'r.fecha_creacion'
            );

        // Por defecto el mapa muestra solo los reclamos activos.
        if (! $request->boolean('resueltos')) {
            $consulta->where('e.nombre', '<>', 'resuelto');
        }

        if ($request->filled('categoria')) {
            $consulta->where('r.categoria_id', (int) $request->query('categoria'));
        }

        if (in_array($request->query('estado'), Reclamos::ESTADOS, true)) {
            $consulta->where('e.nombre', $request->query('estado'));
        }

        $filas = $consulta->orderByDesc('r.fecha_creacion')->limit(1000)->get();

        // Solo el personal municipal recibe el enlace al detalle de gestión.
        $usuario = Auth::user();
        $esMunicipio = $usuario && in_array($usuario->rol, ['operador', 'supervisor'], true);

        return response()->json([
            'total'     => $filas->count(),
            'reportes'  => $filas->map(fn ($f) => [
                'id'        => (int) $f->id,
                'categoria' => $f->categoria,
                'estado'    => $f->estado,
                'lat'       => (float) $f->latitud,
                'lng'       => (float) $f->longitud,
                'barrio'    => $f->barrio,
                'fecha'     => Fecha::corta($f->fecha_creacion),
                'hace'      => Fecha::hace($f->fecha_creacion),
                'url'       => $esMunicipio ? url('/gestion/' . $f->id) : null,
            ])->values(),
        ]);
    }
}
