<?php

// Define el espacio de nombres de la clase.
// Laravel espera encontrar este modelo en App\Models.
namespace App\Models;

// Importa la clase de usuario autenticable de Laravel.
// Nos permite usar las funciones de autenticación: login, contraseñas, etc.
use Illuminate\Foundation\Auth\User as Authenticatable;

// Declara la clase User.
// Extiende Authenticatable para que Laravel pueda autenticar estos usuarios.
class User extends Authenticatable
{
    // Indica el nombre exacto de la tabla de la base de datos.
    // Laravel normalmente buscaría una tabla llamada "users".
    // En SIRCI, la tabla se llama "usuarios".
    protected $table = 'usuarios';

    // Desactiva la gestión automática de created_at y updated_at.
    // Se usa porque esta tabla no necesariamente tiene esas columnas.
    public $timestamps = false;

    // Define qué campos se pueden asignar mediante operaciones masivas,
    // por ejemplo: User::create([...]).
    // No significa que estos sean todos los campos de la tabla.
    protected $fillable = [
        'nombre',        // Nombre del usuario.
        'email',         // Correo electrónico.
        'telefono',      // Teléfono de contacto.
        'password_hash', // Columna donde se almacena el hash de contraseña.
        'rol',           // Rol o tipo de usuario dentro del sistema.
    ];

    // Evita que este campo aparezca en las conversiones del modelo a array
    // o JSON, por ejemplo cuando se devuelve un usuario desde una API.
    // Ayuda a no exponer el hash de la contraseña.
    protected $hidden = [
        'password_hash',
    ];

    // Define conversiones automáticas de tipos para ciertos atributos.
    // Laravel aplicará estas conversiones cuando se lean o asignen valores.
    protected function casts(): array
    {
        return [
            // Al asignar una contraseña en este atributo, Laravel aplica
            // el hasher configurado, si el valor aún no está hasheado.
            // La base guarda el hash, no la contraseña en texto plano.
            'password_hash'  => 'hashed',

            // Convierte el valor de "activo" a booleano (true o false).
            'activo'         => 'boolean',

            // Convierte "fecha_registro" en un objeto de fecha de Laravel.
            'fecha_registro' => 'datetime',
        ];
    }

    // Laravel espera normalmente que el hash de autenticación esté
    // en una columna llamada "password".
    // Este método le indica que debe usar "password_hash".
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    // Laravel usa normalmente una columna "remember_token" para recordar
    // sesiones. Esta tabla no tiene esa columna.
    // Se devuelve un nombre vacío para desactivar ese mecanismo
    // en este modelo.
    public function getRememberTokenName()
    {
        return '';
    }
}
