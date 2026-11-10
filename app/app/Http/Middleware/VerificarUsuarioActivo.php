<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerificarUsuarioActivo
{
    // Cierra las sesiones vigentes de cuentas desactivadas o convertidas
    // en inactivas desde Administración de usuarios.
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario && ! $usuario->activo) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'mensaje' => 'La cuenta está desactivada.',
                ], 401);
            }

            return redirect()->route('login')
                ->withErrors(['email' => 'La cuenta está desactivada. Contactá a un supervisor.']);
        }

        return $next($request);
    }
}
