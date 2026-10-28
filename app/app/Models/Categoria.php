<?php

namespace App\Models;

// Importa la clase base de Laravel para trabajar con modelos y tablas.
use Illuminate\Database\Eloquent\Model;

// Representa las categorías de reclamos almacenadas en la base de datos.
class Categoria extends Model
{
    // Indica el nombre exacto de la tabla que ya existe en nuestra base.
    protected $table = 'categorias';

    // La tabla no tiene las columnas created_at y updated_at de Laravel.
    // Por eso desactivamos el registro automático de esas fechas.
    public $timestamps = false;
}