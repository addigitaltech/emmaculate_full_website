<?php

namespace App\Providers;

use App\Models\Result;
use App\Policies\ResultPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Result::class, ResultPolicy::class);
        RateLimiter::for('contact', fn (Request $request) => Limit::perMinute(5)->by((string) $request->ip()));
        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(10)->by((string) $request->ip()));
        RateLimiter::for('payment-initiation', fn (Request $request) => Limit::perMinute(8)->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));
        RateLimiter::for('payments-webhooks', fn (Request $request) => Limit::perMinute(120)->by((string) $request->ip()));
    }
}
