<?php

use App\Http\Middleware\EnsureUserIsInspector;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

/**
 * Onderdeel: Start de Laravel-webapp en registreert webmiddleware en toegang voor keurmeesters.
 * Eisen: RV-01, RV-02, FE-13, TE-01, TE-06.
 * Bouw: T-01 (applicatiebasis), T-25 (roltoegang), T-31 (structuur).
 * Geplande controle: T-02, T-26, T-32.
 */
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'inspector' => EnsureUserIsInspector::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
