<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRegistrationIsEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificamos si la variable de entorno REGISTRATION_ENABLED está configurada como false
        if (env('REGISTRATION_ENABLED', true) === false) {
            abort(403, 'El registro de nuevos usuarios está deshabilitado temporalmente.');
        }

        return $next($request);
    }
}
