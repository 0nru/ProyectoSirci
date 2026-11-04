<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

// Consultas y reglas compartidas por las pantallas de reclamos.
class Reclamos
{
    // Estados del ciclo de vida, en orden (RN-02).
    public const ESTADOS = ['recibido', 'en proceso', 'resuelto'];

    // Consulta base: un reclamo con su categoría, estado y área.
    // Cada controlador le agrega los filtros que necesita.
    public static function base()
    {
        return DB::table('reportes as r')
            ->join('categorias as c', 'c.id', '=', 'r.categoria_id')
            ->join('estados as e', 'e.id', '=', 'r.estado_id')
            ->join('areas as a', 'a.id', '=', 'r.area_id')
            ->select(
                'r.id',
                'r.usuario_id',
                'r.descripcion',
                'r.barrio',
                'r.latitud',
                'r.longitud',
                'r.fecha_creacion',
                'r.fecha_resolucion',
                'r.categoria_id',
                'r.estado_id',
                'r.area_id',
                'c.nombre as categoria',
                'e.nombre as estado',
                'a.nombre as area'
            );
    }

    // Línea de tiempo de un reclamo, de lo más viejo a lo más nuevo.
    public static function historial(int $reporteId)
    {
        return DB::table('historial_estados as h')
            ->join('estados as e', 'e.id', '=', 'h.estado_id')
            ->leftJoin('areas as a', 'a.id', '=', 'h.area_id')
            ->join('usuarios as u', 'u.id', '=', 'h.usuario_id')
            ->where('h.reporte_id', $reporteId)
            ->orderBy('h.fecha_cambio')
            ->orderBy('h.id')
            ->select(
                'h.id',
                'h.observacion',
                'h.fecha_cambio',
                'e.nombre as estado',
                'a.nombre as area',
                'u.nombre as usuario',
                'u.rol as rol'
            )
            ->get();
    }

    // Estados a los que se puede pasar desde el estado actual (RN-02).
    // Solo un supervisor puede reabrir un reclamo resuelto.
    public static function transiciones(string $actual, string $rol): array
    {
        $mapa = [
            'recibido'   => ['en proceso'],
            'en proceso' => ['resuelto'],
            'resuelto'   => $rol === 'supervisor' ? ['en proceso'] : [],
        ];

        return $mapa[$actual] ?? [];
    }
}
