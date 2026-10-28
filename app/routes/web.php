
<?php

// Importa el controlador de autenticación.
use App\Http\Controllers\AuthController;

// Importa el controlador encargado de los reclamos.
use App\Http\Controllers\ReporteController;

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

    // Muestra el panel.
    Route::get('/panel', fn () => view('panel'));

    // Muestra el formulario para registrar un reclamo.
    Route::get('/reclamos/nuevo', [ReporteController::class, 'crear'])
        ->name('reclamos.crear');

    // Recibe y procesa el formulario de reclamos.
    Route::post('/reclamos', [ReporteController::class, 'guardar'])
        ->name('reclamos.guardar');

    // Cierra la sesión.
    Route::post('/logout', [AuthController::class, 'logout']);

});
