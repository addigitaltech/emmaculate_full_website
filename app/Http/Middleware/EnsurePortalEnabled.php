<?php

namespace App\Http\Middleware;

use App\Domain\Auth\Support\PortalAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Signs out students and parents the moment the school switches their portal off. */
class EnsurePortalEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $message = $user ? PortalAccess::blockedMessage($user) : null;
        if ($message !== null) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => $message]);
        }

        return $next($request);
    }
}
