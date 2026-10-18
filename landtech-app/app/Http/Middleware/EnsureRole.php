<?php

namespace App\Http\Middleware;

use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server-side role gate.
 *
 * Usage: ->middleware('role:citizen') or 'role:admin,registry_officer'.
 *
 * This is authoritative, not cosmetic — hiding a menu item is never treated as
 * access control. A user whose roles do not match receives 403 even when typing
 * the URL directly. There is no citizen bypass for officer/admin routes.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || $roles === []) {
            abort(403);
        }

        if (! $user->hasAnyRole($roles)) {
            abort(403, __('citizen.forbidden'));
        }

        return $next($request);
    }
}
