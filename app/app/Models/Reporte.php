<?php

namespace App\Models;

// Importa la clase base de Laravel para trabajar con tablas.
use Illuminate\Database\Eloquent\Model;

// Representa un reclamo registrado en SIRCI.
class Reporte extends Model
{
    // Vincula este modelo con la tabla existente "reportes".
    protected $table = 'reportes';

    // La tabla no utiliza las fechas automáticas de Laravel.
    public $timestamps = false;

    // Define los campos que Laravel tiene permitido completar
    // mediante operaciones como Reporte::create().
    protected $fillable = [
        'usuario_id',   // Ciudadano que registra el reclamo.
        'categoria_id', // Tipo de problema seleccionado.
        'area_id',      // Área municipal asignada por la categoría.
        'estado_id',    // Estado actual del reclamo.
        'descripcion',  // Explicación del problema.
        'barrio',       // Guardará la dirección o referencia ingresada.
        'latitud',      // Coordenada norte/sur del lugar.
        'longitud',     // Coordenada este/oeste del lugar.
    ];
}
