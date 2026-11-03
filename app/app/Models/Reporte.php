<?php

namespace App\Models;

// Importa la clase base de Laravel para trabajar con tablas.
use Illuminate\Database\Eloquent\Model;

// Permite declarar relaciones entre modelos.
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Representa un reclamo registrado en SIRCI.
class Reporte extends Model
{
    // Vincula este modelo con la tabla existente "reportes".
    protected $table = 'reportes';

    // La tabla no utiliza las fechas automáticas de Laravel.
    public $timestamps = false;

    /*
     * DECISIÓN DE DISEÑO (direccion -> barrio):
     * El formulario muestra "Dirección o referencia del lugar" (campo "direccion"),
     * pero se guarda en la columna existente "barrio" para no modificar la base.
     * Por eso "barrio" contiene texto libre del ciudadano (una calle, una esquina
     * o una referencia) y NO debe usarse como nombre de barrio en estadísticas.
     */

    // Define los campos que Laravel tiene permitido completar
    // mediante operaciones como Reporte::create().
    protected $fillable = [
        'usuario_id',   // Ciudadano que registra el reclamo.
        'categoria_id', // Tipo de problema seleccionado.
        'area_id',      // Área municipal asignada por la categoría.
        'estado_id',    // Estado actual del reclamo.
        'descripcion',  // Explicación del problema.
        'barrio',       // Guarda la dirección o referencia ingresada.
        'latitud',      // Coordenada norte/sur del lugar.
        'longitud',     // Coordenada este/oeste del lugar.
    ];

    // Convierte fecha_creacion en un objeto de fecha para formatearla en las vistas.
    protected function casts(): array
    {
        return [
            'fecha_creacion' => 'datetime',
        ];
    }

    // Un reclamo pertenece a una categoría.
    // Relaciona reportes.categoria_id con categorias.id.
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    // Un reclamo tiene un estado actual.
    // Relaciona reportes.estado_id con estados.id.
    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class, 'estado_id');
    }
}
