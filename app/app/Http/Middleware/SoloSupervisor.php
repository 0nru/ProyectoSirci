<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SoloSupervisor
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            $request->user()?->rol === 'supervisor',
            403,
            'Esta sección es exclusiva de supervisores.'
        );

        return $next($request);
    }
}
