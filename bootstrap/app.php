<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsurePortalEnabled;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\SecurityHeaders;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([__DIR__.'/../app/Console/Commands'])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'role' => EnsureRole::class,
            'permission' => EnsurePermission::class,
            'portal.enabled' => EnsurePortalEnabled::class,
        ]);
        $middleware->prepend(\App\Http\Middleware\BlockProbingRequests::class);
        $middleware->web(prepend: [\App\Http\Middleware\ConfigureMediaUrl::class]);
        $middleware->web(append: ['throttle:site']);
        $middleware->append(SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (HttpException $exception, Request $request) {
            if ($exception->getStatusCode() !== 419) {
                return null;
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your session expired. Please refresh and try again.'], 419);
            }

            $field = $request->routeIs('student.login') ? 'student_id' : 'email';

            return redirect()->back()->withErrors([$field => 'Your session expired. Please try again.']);
        });

        $exceptions->render(function (HttpException $exception, Request $request) {
            if ($exception->getStatusCode() !== 403 || ! $request->is('admin') || ! Filament::auth()->check()) {
                return null;
            }

            $user = Filament::auth()->user();
            $panel = Filament::getCurrentPanel();

            if (! $user instanceof FilamentUser || $user->canAccessPanel($panel)) {
                return null;
            }

            Filament::auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->guest(Filament::getLoginUrl())
                ->withErrors(['email' => 'This account is not authorized for the administrator panel. Please use the correct portal.']);
        });
    })->create();
