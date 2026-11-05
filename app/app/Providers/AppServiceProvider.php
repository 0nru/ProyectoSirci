<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        // Comparte las notificaciones con la plantilla principal de SIRCI.
    \Illuminate\Support\Facades\View::composer('layouts.app', function ($vista) {

    $usuario = \Illuminate\Support\Facades\Auth::user();

    // La campana de notificaciones corresponde al ciudadano.
    if (! $usuario || $usuario->rol !== 'ciudadano') {
        $vista->with('notifNoLeidas', 0);
        $vista->with('notifRecientes', collect());
        return;
    }

    // Consultamos únicamente las notificaciones del usuario autenticado.
    $consulta = \Illuminate\Support\Facades\DB::table('notificaciones')
        ->where('usuario_id', $usuario->id);

    // Cantidad de notificaciones pendientes de lectura.
    $vista->with(
        'notifNoLeidas',
        (clone $consulta)->where('leida', 0)->count()
    );

    // Las cinco notificaciones más recientes para la campana.
    $vista->with(
        'notifRecientes',
        (clone $consulta)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit(5)
            ->get()
    );
});
    }
}
