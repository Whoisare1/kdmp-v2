<?php

use App\Http\Middleware\EnsureIdempotentRequest;
use App\Http\Middleware\PeranMiddleware;
use App\Http\Middleware\RestrictPurchasingRole;
use App\Http\Middleware\SetEntitasAktif;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', SetEntitasAktif::class);
        $middleware->appendToGroup('web', RestrictPurchasingRole::class);
        $middleware->alias([
            'super_admin' => SuperAdminMiddleware::class,
            'peran'       => PeranMiddleware::class,
            'idempotent'  => EnsureIdempotentRequest::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
