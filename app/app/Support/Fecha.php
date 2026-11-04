<?php

namespace App\Support;

use Carbon\Carbon;

// Formatea fechas para las vistas en español.
// Las fechas llegan como texto desde la base (consultas con DB::table).
class Fecha
{
    // Convierte el valor recibido en un objeto Carbon.
    public static function parse($valor): Carbon
    {
        return $valor instanceof Carbon ? $valor : Carbon::parse($valor);
    }

    // Ejemplo: "14 sep 2026"
    public static function corta($valor): string
    {
        return self::parse($valor)->translatedFormat('j M Y');
    }

    // Ejemplo: "14 sep 2026, 09:42"
    public static function conHora($valor): string
    {
        return self::parse($valor)->translatedFormat('j M Y, H:i');
    }

    // Ejemplo: "hace 3 días"
    public static function hace($valor): string
    {
        return self::parse($valor)->diffForHumans();
    }
}
