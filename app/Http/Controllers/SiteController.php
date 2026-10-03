<?php

namespace App\Http\Controllers;

use App\Models\AcademicProgramme;
use App\Models\AdmissionApplication;
use App\Models\AdmissionsSettings;
use App\Models\Announcement;
use App\Models\CmsPage;
use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\FooterSection;
use App\Models\GalleryAlbum;
use App\Models\HeroSlide;
use App\Models\LeadershipProfile;
use App\Models\MediaAsset;
use App\Models\NavigationItem;
use App\Models\NewsPost;
use App\Models\SchoolEvent;
use App\Models\SchoolSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function home(): View
    {
        return $this->render('site.home', [
            'slides' => HeroSlide::query()->where('is_enabled', true)->orderBy('sort_order')->get(),
            'programmes' => AcademicProgramme::query()->where('is_published', true)->orderBy('sort_order')->get(),
            'news' => NewsPost::published()->with('coverImage')->orderByDesc('published_at')->limit(3)->get(),
            'events' => SchoolEvent::published()->orderBy('starts_at')->limit(3)->get(),
            'leaders' => LeadershipProfile::query()->where('is_visible', true)->with('photo')->orderBy('sort_order')->limit(3)->get(),
            'albums' => GalleryAlbum::query()->where('status', 'published')->with(['coverImage', 'images'])->orderBy('sort_order')->limit(3)->get(),
            'announcements' => Announcement::visibleNow()->with('image')->orderBy('sort_order')->orderByDesc('starts_at')->limit(3)->get(),
            'stats' => SchoolSettings::current()->verified_stats ?? [],
        ]);
    }

    public function page(string $slug): View
    {
        $page = CmsPage::published()->with('featuredMedia')->where('slug', $slug)->firstOrFail();
        $mediaIds = collect($page->content_blocks ?? [])->pluck('media_id')->filter()->unique();
        return $this->render('site.page', [
            'page' => $page,
            'contentMedia' => MediaAsset::query()->whereIn('id', $mediaIds)->get()->keyBy('id'),
            'seoTitle' => $page->seo_title ?: $page->title,
            'seoDescription' => $page->seo_description ?: $page->excerpt,
        ]);
    }

    public function academics(): View
    {
        return $this->render('site.academics', [
            'programmes' => AcademicProgramme::query()->where('is_published', true)->with('image')->orderBy('sort_order')->get(),
        ]);
    }

    public function programme(string $slug): View
    {
        $programme = AcademicProgramme::query()->where('is_published', true)->where('slug', $slug)->with('image')->firstOrFail();
        return $this->render('site.programme', ['programme' => $programme, 'seoTitle' => $programme->seo_title ?: $programme->title, 'seoDescription' => $programme->seo_description ?: $programme->intro]);
    }

    public function admissions(): View
    {
        return $this->render('site.admissions', ['admissions' => AdmissionsSettings::current()->load('image')]);
    }

    public function leadership(): View
    {
        return $this->render('site.leadership', ['leaders' => LeadershipProfile::query()->where('is_visible', true)->with('photo')->orderBy('sort_order')->get()]);
    }

    public function news(): View
    {
        return $this->render('site.news.index', ['posts' => NewsPost::published()->with('coverImage')->orderByDesc('published_at')->paginate(9)]);
    }

    public function newsShow(string $slug): View
    {
        $post = NewsPost::published()->with('coverImage', 'author')->where('slug', $slug)->firstOrFail();
        return $this->render('site.news.show', ['post' => $post, 'seoTitle' => $post->seo_title ?: $post->title, 'seoDescription' => $post->seo_description ?: $post->excerpt]);
    }

    public function events(): View
    {
        return $this->render('site.events.index', ['events' => SchoolEvent::published()->orderByRaw('starts_at IS NULL')->orderBy('starts_at')->paginate(9)]);
    }

    public function announcements(): View
    {
        return $this->render('site.announcements', ['announcements' => Announcement::visibleNow()->with('image')->orderBy('sort_order')->orderByDesc('starts_at')->paginate(12)]);
    }

    public function eventShow(string $slug): View
    {
        $event = SchoolEvent::published()->with('image')->where('slug', $slug)->firstOrFail();
        return $this->render('site.events.show', ['event' => $event, 'seoTitle' => $event->seo_title ?: $event->title, 'seoDescription' => $event->seo_description ?: $event->description]);
    }

    public function gallery(): View
    {
        return $this->render('site.gallery', ['albums' => GalleryAlbum::query()->where('status', 'published')->with(['coverImage', 'images.media'])->orderBy('sort_order')->get()]);
    }

    public function faq(): View
    {
        return $this->render('site.faq', ['faqs' => Faq::query()->where('is_visible', true)->orderBy('sort_order')->get()]);
    }

    public function contact(): View
    {
        return $this->render('site.contact');
    }

    public function storeContact(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'subject' => ['nullable', 'string', 'max:160'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'website' => ['nullable', 'max:0'],
        ]);
        unset($data['website']);
        $key = 'contact:'.hash('sha256', (string) $request->ip());
        if (RateLimiter::tooManyAttempts($key, 4)) {
            return back()->withErrors(['message' => 'Please wait a few minutes before sending another message.'])->withInput();
        }
        RateLimiter::hit($key, 300);
        ContactMessage::query()->create($data + [
            'status' => 'new',
            'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
            'received_at' => now(),
        ]);
        return redirect()->route('contact')->with('success', 'Thank you. Your message has been received.');
    }

    public function storeAdmissionApplication(Request $request): RedirectResponse
    {
        $settings = AdmissionsSettings::current();
        abort_unless($settings->status === 'open', 404);
        $data = $request->validate([
            'applicant_name' => ['required', 'string', 'max:160'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'applying_for' => ['required', 'string', 'max:120'],
            'guardian_name' => ['required', 'string', 'max:160'],
            'guardian_email' => ['nullable', 'email:rfc', 'max:190'],
            'guardian_phone' => ['required', 'string', 'max:40'],
            'message' => ['nullable', 'string', 'max:3000'],
        ]);
        $application = AdmissionApplication::query()->create($data + [
            'reference' => 'EA-'.Str::upper(Str::random(12)),
            'status' => 'submitted',
            'metadata' => [],
            'submitted_at' => now(),
        ]);
        return redirect()->route('admissions')->with('application_reference', $application->reference);
    }

    private function render(string $view, array $data = []): View
    {
        $base = [
            'settings' => SchoolSettings::current(),
            'navigation' => NavigationItem::query()->where('menu', 'main')->whereNull('parent_id')->where('is_visible', true)->with('children')->orderBy('sort_order')->get(),
            'footerSections' => FooterSection::query()->where('is_visible', true)->orderBy('sort_order')->get(),
        ];
        return view($view, array_merge($base, $data));
    }
}
