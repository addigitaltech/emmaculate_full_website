<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $allowed = array_values(array_filter(array_map(
            static fn (string $role): string => trim($role),
            preg_split('/[,|]/', implode(',', $roles)) ?: [],
        )));
        abort_unless($request->user() && $allowed && $request->user()->hasAnyRole($allowed), 403);
        return $next($request);
    }
}
