<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Storage;

class EnsureRegistrationIsEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificamos si existe el archivo de bloqueo. 
        // Si existe 'registration_disabled', entonces NO se permite el registro.
        if (Storage::exists('registration_disabled')) {
            abort(403, 'El registro de nuevos usuarios está deshabilitado temporalmente.');
        }

        return $next($request);
    }
}
