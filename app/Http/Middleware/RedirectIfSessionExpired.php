<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfSessionExpired
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Check if the response is a 419 (Page Expired) error
        if ($response->getStatusCode() === 419) {
            // If it's an AJAX request, return JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your session has expired. Please refresh and try again.'
                ], 419);
            }
            
            // Otherwise, redirect to login with a message
            return redirect()->guest(route('login'))
                ->with('status', 'Your session has expired. Please log in again.');
        }

        return $response;
    }
}
