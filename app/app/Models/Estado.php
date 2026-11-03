<?php

namespace App\Models;

// Importa la clase base de Laravel para trabajar con modelos y tablas.
use Illuminate\Database\Eloquent\Model;

// Representa los estados de un reclamo (recibido, en proceso, resuelto).
class Estado extends Model
{
    // Indica el nombre exacto de la tabla que ya existe en nuestra base.
    protected $table = 'estados';

    // La tabla no tiene las columnas created_at y updated_at de Laravel.
    public $timestamps = false;
}
