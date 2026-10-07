<?php

namespace App\Providers;

use App\Domain\Website\Support\SafePublicUrl;
use App\Models\FooterSection;
use App\Models\NavigationItem;
use App\Models\PortalLink;
use App\Models\Result;
use App\Models\SchoolSettings;
use App\Policies\ResultPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
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

        // Auth and staff views extend the public layout but their controllers do not all pass these values.
        View::composer(['auth.*', 'portal.score-entry', 'portal.staff.*', 'site.check-result'], function ($view): void {
            $data = $view->getData();
            $view->with([
                'settings' => $data['settings'] ?? SchoolSettings::current(),
                'navigation' => $data['navigation'] ?? NavigationItem::query()->where('menu', 'main')->whereNull('parent_id')->where('is_visible', true)->with('children')->orderBy('sort_order')->get(),
                'footerSections' => $data['footerSections'] ?? FooterSection::query()->where('is_visible', true)->orderBy('sort_order')->get(),
            ]);
        });

        View::composer('site.layout', function ($view): void {
            $data = $view->getData();
            $settings = $data['settings'] ?? SchoolSettings::current();
            $icons = ['facebook', 'twitter', 'instagram', 'youtube', 'tiktok', 'linkedin', 'whatsapp'];
            $social = [];
            foreach (($settings->social_links ?? []) as $network => $url) {
                $key = strtolower((string) $network);
                $icon = $key === 'x' ? 'twitter' : $key;
                if (filled($url) && SafePublicUrl::allows((string) $url) && in_array($icon, $icons, true)) {
                    $social[] = ['label' => ucfirst($key), 'url' => (string) $url, 'icon' => $icon];
                }
            }
            $digits = preg_replace('/\D+/', '', (string) $settings->whatsapp);
            if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
                $digits = '234'.substr($digits, 1);
            }
            $portalLinks = $data['portalLinks'] ?? PortalLink::query()->publiclyVisible()->get();
            foreach ($portalLinks as $link) {
                $off = ($link->key === 'students' && ! $settings->student_portal_enabled)
                    || ($link->key === 'parents' && ! $settings->parent_portal_enabled);
                if ($off) {
                    $link->status = 'coming_soon';
                }
            }
            if ($settings->public_result_check_enabled && ! $portalLinks->contains(fn (PortalLink $link) => $link->key === 'result-check')) {
                $check = new PortalLink();
                $check->forceFill([
                    'key' => 'result-check',
                    'title' => 'Check Result',
                    'description' => 'Use your admission number and surname',
                    'url' => '/check-result',
                    'status' => 'live',
                    'sort_order' => 0,
                ]);
                $portalLinks->push($check);
            }
            $view->with([
                'portalLinks' => $portalLinks,
                'socialLinks' => $social,
                'whatsappUrl' => strlen($digits) >= 10 ? 'https://wa.me/'.$digits : null,
                'currentPath' => trim(request()->path(), '/'),
            ]);
        });

        RateLimiter::for('contact', fn (Request $request) => Limit::perMinute(5)->by((string) $request->ip()));
        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(10)->by((string) $request->ip()));
        RateLimiter::for('payment-initiation', fn (Request $request) => Limit::perMinute(8)->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));
        RateLimiter::for('payments-webhooks', fn (Request $request) => Limit::perMinute(120)->by((string) $request->ip()));
    }
}
