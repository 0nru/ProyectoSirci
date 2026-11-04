<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/*
 * Protege las páginas destinadas al personal municipal.
 *
 * Permite entrar a operadores y supervisores.
 * Los ciudadanos no tienen autorización.
 */
class SoloMunicipio
{
    public function handle(Request $request, Closure $next): Response
    {
        // Recupera al usuario que inició sesión.
        $usuario = $request->user();

        // Verifica que exista un usuario y tenga un rol municipal.
        abort_unless(
            $usuario && in_array(
                $usuario->rol,
                ['operador', 'supervisor'],
                true
            ),
            403,
            'Esta sección es solo para el personal municipal.'
        );

        // Si está autorizado, continúa hacia la página solicitada.
        return $next($request);
    }
}