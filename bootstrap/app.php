<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
<<<<<<< HEAD
=======
use App\Http\Middleware\RedirectIfSessionExpired;
>>>>>>> myNotesInvestigation

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
<<<<<<< HEAD
        //
=======
        $middleware->web(append: [
            RedirectIfSessionExpired::class,
        ]);
>>>>>>> myNotesInvestigation
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
