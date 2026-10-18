<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\SetLocale;
use App\Support\Auth\LandingPage;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
        ]);

        // Server-side role gate. Usage: 'role:citizen', 'role:admin,registry_officer'.
        $middleware->alias([
            'role' => EnsureRole::class,
        ]);

        // Authenticated users hitting guest-only pages (login/register) are sent
        // to their own role's landing dashboard. Delegated to LandingPage so the
        // rule lives in exactly one place — see that class for the documented
        // role precedence.
        $middleware->redirectUsersTo(function (Request $request) {
            return route(LandingPage::routeNameFor($request->user()));
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
