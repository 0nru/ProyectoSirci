
<?php

use Illuminate\Support\Facades\Route;

// Autenticación y registro.
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RegistroController;

// Paneles y reclamos del ciudadano.
use App\Http\Controllers\PanelController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\MisReclamosController;

// Funcionalidades del personal municipal.
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GestionController;

// Control de acceso para operadores y supervisores.
use App\Http\Middleware\SoloMunicipio;


/*
|--------------------------------------------------------------------------
| INICIO
|--------------------------------------------------------------------------
|
| La dirección principal envía al panel correspondiente.
| Si el usuario no inició sesión, Laravel lo llevará al login.
|
*/

Route::get('/', fn () => redirect()->route('panel'));

/*
|--------------------------------------------------------------------------
| MAPA PÚBLICO DE RECLAMOS
|--------------------------------------------------------------------------
|
| Disponible sin iniciar sesión.
| Muestra la ubicación de los reclamos y permite filtrarlos.
|
*/

// Pantalla del mapa.
Route::get('/mapa', [\App\Http\Controllers\MapaController::class, 'index'])
    ->name('mapa');

// Datos de los reclamos en formato JSON para Leaflet.
Route::get('/mapa/datos', [\App\Http\Controllers\MapaController::class, 'datos'])
    ->name('mapa.datos');


/*
|--------------------------------------------------------------------------
| RUTAS PARA VISITANTES
|--------------------------------------------------------------------------
|
| Login y registro.
| Solo están disponibles para usuarios sin sesión iniciada.
|
*/

Route::middleware('guest')->group(function () {

    // Inicio de sesión.
    Route::get('/login', [AuthController::class, 'mostrarLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login']);

    // Registro de nuevos ciudadanos.
    Route::get('/registro', [RegistroController::class, 'mostrar'])
        ->name('registro');

    Route::post('/registro', [RegistroController::class, 'registrar']);

});


/*
|--------------------------------------------------------------------------
| RUTAS PARA USUARIOS AUTENTICADOS
|--------------------------------------------------------------------------
|
| Todas estas rutas requieren una sesión iniciada.
|
*/

Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | PANEL PRINCIPAL
    |--------------------------------------------------------------------------
    |
    | El ciudadano ve su panel personal.
    | El operador y el supervisor son redirigidos a Gestión.
    | Esa decisión la toma PanelController.
    |
    */

    Route::get('/panel', [PanelController::class, 'index'])
        ->name('panel');


    /*
    |--------------------------------------------------------------------------
    | RECLAMOS DEL CIUDADANO
    |--------------------------------------------------------------------------
    */

    // Listado de reclamos propios.
    Route::get('/reclamos', [MisReclamosController::class, 'index'])
        ->name('reclamos.index');

    // Formulario para crear un reclamo.
    // Conservamos nuestro controlador y el mapa existente.
    Route::get('/reclamos/nuevo', [ReporteController::class, 'crear'])
        ->name('reclamos.crear');

    // Guardar un nuevo reclamo.
    Route::post('/reclamos', [ReporteController::class, 'guardar'])
        ->name('reclamos.guardar');

    // Detalle y seguimiento de un reclamo propio.
    Route::get('/reclamos/{reporte}', [MisReclamosController::class, 'ver'])
        ->whereNumber('reporte')
        ->name('reclamos.ver');


    /*
    |--------------------------------------------------------------------------
    | INDICADORES MUNICIPALES
    |--------------------------------------------------------------------------
    |
    | Disponibles para operadores y supervisores.
    |
    */

    Route::get('/indicadores', [DashboardController::class, 'index'])
        ->middleware(SoloMunicipio::class)
        ->name('indicadores');


    /*
    |--------------------------------------------------------------------------
    | GESTIÓN DE RECLAMOS
    |--------------------------------------------------------------------------
    |
    | Pantalla principal del personal municipal.
    | Solamente operadores y supervisores pueden acceder.
    |
    */

    Route::middleware(SoloMunicipio::class)->group(function () {

        // Listado general con filtros.
        Route::get('/gestion', [GestionController::class, 'index'])
            ->name('gestion.index');

        // Detalle de un reclamo.
        Route::get('/gestion/{reporte}', [GestionController::class, 'ver'])
            ->whereNumber('reporte')
            ->name('gestion.ver');

        // Cambiar estado de un reclamo.
        Route::post('/gestion/{reporte}/estado', [GestionController::class, 'cambiarEstado'])
            ->whereNumber('reporte')
            ->name('gestion.estado');

        // Asignar o reasignar el área responsable.
        Route::post('/gestion/{reporte}/area', [GestionController::class, 'asignarArea'])
            ->whereNumber('reporte')
            ->name('gestion.area');

    });


    /*
    |--------------------------------------------------------------------------
    | CERRAR SESIÓN
    |--------------------------------------------------------------------------
    */

    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');

});
