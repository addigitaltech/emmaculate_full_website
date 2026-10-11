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
        // The sign-in pages and score entry extend the site layout but their controllers do not pass the
        // school settings, navigation or footer, which made /login return a server error. Supply them here.
        // Editors never type web addresses: they are made from the title. Published items get a publish date automatically.
        foreach ([\App\Models\NewsPost::class, \App\Models\SchoolEvent::class, \App\Models\AcademicProgramme::class, \App\Models\GalleryAlbum::class] as $sluggedModel) {
            $sluggedModel::saving(function ($model): void {
                if (blank($model->slug) && filled($model->title)) {
                    $model->slug = \App\Support\Slugger::unique($model, (string) $model->title);
                }
            });
        }
        foreach ([\App\Models\NewsPost::class, \App\Models\SchoolEvent::class, \App\Models\CmsPage::class] as $datedModel) {
            $datedModel::saving(function ($model): void {
                if ($model->status === 'published' && ! $model->published_at) {
                    $model->published_at = now();
                }
            });
        }
        \App\Models\NewsPost::saving(function ($post): void {
            if (! $post->author_id && auth()->check()) {
                $post->author_id = auth()->id();
            }
        });
        foreach ([\App\Models\SchoolSettings::class, \App\Models\NavigationItem::class, \App\Models\FooterSection::class, \App\Models\PortalLink::class, \App\Models\CmsPage::class, \App\Models\CmsSection::class, \App\Models\HeroSlide::class, \App\Models\AcademicProgramme::class, \App\Models\NewsPost::class, \App\Models\SchoolEvent::class, \App\Models\Announcement::class, \App\Models\GalleryAlbum::class, \App\Models\GalleryImage::class, \App\Models\MediaAsset::class, \App\Models\Faq::class, \App\Models\LeadershipProfile::class, \App\Models\AdmissionsSettings::class] as $cachedModel) {
            $cachedModel::saved(fn () => \App\Support\SiteCache::flush());
            $cachedModel::deleted(fn () => \App\Support\SiteCache::flush());
        }
        View::composer(['auth.*', 'portal.score-entry', 'portal.staff.*', 'site.check-result'], function ($view): void {
            $data = $view->getData();
            $view->with([
                'settings' => $data['settings'] ?? SchoolSettings::current(),
                'navigation' => $data['navigation'] ?? NavigationItem::query()->where('menu', 'main')->whereNull('parent_id')->where('is_visible', true)->with('children')->orderBy('sort_order')->get(),
                'footerSections' => $data['footerSections'] ?? FooterSection::query()->where('is_visible', true)->orderBy('sort_order')->get(),
            ]);
        });
        View::composer('site.layout', function ($view): void {
            $settings = $view->getData()['settings'] ?? SchoolSettings::current();
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
            $portalLinks = \App\Support\SiteCache::remember('portal-links', 600, fn () => PortalLink::query()->publiclyVisible()->get());
            foreach ($portalLinks as $link) {
                $off = ($link->key === 'students' && ! $settings->student_portal_enabled) || ($link->key === 'parents' && ! $settings->parent_portal_enabled);
                if ($off) {
                    $link->status = 'coming_soon'; // display only, never saved
                }
            }
            if ($settings->public_result_check_enabled) {
                $check = new PortalLink();
                $check->forceFill(['key' => 'result-check', 'title' => 'Check Result', 'description' => 'Use your admission number and surname', 'url' => '/check-result', 'status' => 'live', 'sort_order' => 0]);
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
        // Many students may sign in or look up results from one school network, so these allow more per IP.
        // Brute-force protection for them is the per-admission-number lockout in the controllers.
        RateLimiter::for('student-auth', fn (Request $request) => Limit::perMinute(60)->by((string) $request->ip()));
        RateLimiter::for('results', fn (Request $request) => Limit::perMinute(60)->by((string) $request->ip()));
        RateLimiter::for('payment-initiation', fn (Request $request) => Limit::perMinute(8)->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));
        RateLimiter::for('payments-webhooks', fn (Request $request) => Limit::perMinute(120)->by((string) $request->ip()));
    }
}
