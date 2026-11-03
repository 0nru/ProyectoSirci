<?php

// Importa el controlador de autenticación.
use App\Http\Controllers\AuthController;

// Importa el controlador encargado de los reclamos.
use App\Http\Controllers\ReporteController;

// Importa el controlador de ubicaciones del supervisor.
use App\Http\Controllers\Supervisor\UbicacionController;

// Permite definir las rutas de Laravel.
use Illuminate\Support\Facades\Route;


// Página principal: redirige al panel.
// Si el usuario no inició sesión, el middleware auth lo llevará al login.
Route::get('/', fn () => redirect('/panel'));

// Rutas para visitantes que todavía no iniciaron sesión.
Route::middleware('guest')->group(function () {

    // Muestra el formulario de inicio de sesión.
    Route::get('/login', [AuthController::class, 'mostrarLogin'])
        ->name('login');

    // Procesa las credenciales del formulario.
    Route::post('/login', [AuthController::class, 'login']);

});


// Rutas para usuarios que ya iniciaron sesión.
Route::middleware('auth')->group(function () {

// Muestra las ubicaciones agrupadas geográficamente para el supervisor.
Route::get('/supervisor/ubicaciones', [UbicacionController::class, 'index'])
    ->name('supervisor.ubicaciones');
        // Muestra los reclamos individuales de una ubicación agrupada.
    // {referencia} es el ID del reclamo que funciona como punto de referencia del grupo.
    Route::get('/supervisor/ubicaciones/{referencia}', [UbicacionController::class, 'show'])
        ->whereNumber('referencia')
        ->name('supervisor.ubicaciones.show');

    // Muestra el panel.
    // Muestra el panel que corresponde al rol del usuario autenticado.
Route::get('/panel', function () {

    // Lee el rol del usuario que tiene la sesión iniciada.
    $rol = auth()->user()->rol;

    // Compara el rol y devuelve la vista correspondiente.
    return match ($rol) {

        // El ciudadano ve su panel habitual.
        'ciudadano' => view('panel'),

        // El supervisor ve su panel administrativo.
        'supervisor' => view('supervisor.panel'),

        // Cualquier otro rol queda bloqueado.
        default => abort(403, 'Acceso no autorizado.'),
    };

})->name('panel');

    // Muestra la lista de reclamos del ciudadano autenticado.
Route::get('/reclamos', [ReporteController::class, 'misReclamos'])
    ->name('reclamos.index');

    // Muestra el formulario para registrar un reclamo.
    Route::get('/reclamos/nuevo', [ReporteController::class, 'crear'])
        ->name('reclamos.crear');

    // Recibe y procesa el formulario de reclamos.
    Route::post('/reclamos', [ReporteController::class, 'guardar'])
        ->name('reclamos.guardar');

    // Cierra la sesión.
    Route::post('/logout', [AuthController::class, 'logout']);

});
