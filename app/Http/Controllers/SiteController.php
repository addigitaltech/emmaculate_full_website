<?php

namespace App\Http\Controllers;

use App\Domain\Website\Support\PageBlocks;
use App\Domain\Website\Support\SiteSections;
use App\Support\SiteCache;
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
use App\Mail\SchoolUpdateMail;
use App\Models\NewsletterSubscriber;
use App\Models\PortalLink;
use App\Models\SchoolEvent;
use App\Models\SchoolSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SiteController extends Controller
{
    /** Default page-banner photographs (all shipped with the project). Admins can override them, see heroUrl(). */
    private const HERO_DEFAULTS = [
        'about' => 'migrated-images/students-reading.jpg',
        'history' => 'migrated-images/students-reading.jpg',
        'mission' => 'migrated-images/students-reading.jpg',
        'academics' => 'migrated-images/lab.jpg',
        'admissions' => 'migrated-images/textbooks.jpg',
        'news' => 'migrated-images/school-gate.jpg',
        'events' => 'migrated-images/school-gate.jpg',
        'gallery' => 'migrated-images/textbooks.jpg',
        'leadership' => 'migrated-images/students-reading.jpg',
        'contact' => 'migrated-images/school-gate.jpg',
        'faq' => 'migrated-images/students-group.jpg',
        'portal' => 'migrated-images/students-group.jpg',
        'page' => 'migrated-images/students-reading.jpg',
    ];

    public function home(): View
    {
        $data = SiteCache::remember('home', 300, function (): array {
            $about = CmsPage::published()->where('slug', 'about')->first();

            return [
                'slides' => HeroSlide::query()->where('is_enabled', true)->with('image')->orderBy('sort_order')->get(),
                'programmes' => AcademicProgramme::query()->where('is_published', true)->with('image')->orderBy('sort_order')->limit(3)->get(),
                'news' => NewsPost::published()->with('coverImage')->orderByDesc('published_at')->limit(3)->get(),
                'events' => SchoolEvent::published()->upcoming()->orderByRaw('starts_at IS NULL')->orderBy('starts_at')->limit(3)->get(),
                'announcements' => Announcement::visibleNow()->with('image')->orderBy('sort_order')->orderByDesc('starts_at')->limit(3)->get(),
                'aboutParagraphs' => $about ? PageBlocks::paragraphs($about->content_blocks ?? [], 2) : [],
            ];
        });

        return $this->render('site.home', $data + [
            'stats' => $this->settings()->verified_stats ?? [],
            'aboutImage' => asset('storage/migrated-images/students-reading.jpg'),
            'seoDescription' => $this->settings()->description,
        ]);
    }

    public function about(): View
    {
        $page = CmsPage::published()->with('featuredMedia')->where('slug', 'about')->first();
        $mvPage = CmsPage::published()->where('slug', 'mission-vision')->first();
        $groups = $this->identityGroups($mvPage ? PageBlocks::keyed($mvPage->content_blocks ?? []) : []);

        return $this->render('site.about', [
            'page' => $page,
            'paragraphs' => $page ? PageBlocks::paragraphs($page->content_blocks ?? [], 4) : [],
            'mission' => $groups['mission']['paragraphs'][0] ?? null,
            'vision' => $groups['vision']['paragraphs'][0] ?? null,
            'aboutImage' => asset('storage/migrated-images/textbooks.jpg'),
            'leaders' => LeadershipProfile::query()->where('is_visible', true)->with('photo')->orderBy('sort_order')->limit(2)->get(),
            'heroImage' => $this->heroUrl('about', $page?->featuredMedia),
            'seoTitle' => $page?->seo_title ?: 'About Us',
            'seoDescription' => $page?->seo_description ?: $page?->excerpt,
        ]);
    }

    public function history(): View
    {
        $page = CmsPage::published()->where('slug', 'history')->first();
        $chapters = $page ? PageBlocks::chapters($page->content_blocks ?? []) : [];
        $mediaIds = collect($chapters)->pluck('media_id')->filter()->unique()->all();

        return $this->render('site.history', [
            'page' => $page,
            'chapters' => $chapters,
            'contentMedia' => MediaAsset::query()->whereIn('id', $mediaIds)->get()->keyBy('id'),
            'heroImage' => $this->heroUrl('history'),
            'seoTitle' => $page?->seo_title ?: 'Our History',
            'seoDescription' => $page?->seo_description ?: $page?->excerpt,
        ]);
    }

    public function missionVision(): View
    {
        $page = CmsPage::published()->where('slug', 'mission-vision')->first();

        return $this->render('site.mission', [
            'page' => $page,
            'groups' => $this->identityGroups($page ? PageBlocks::keyed($page->content_blocks ?? []) : []),
            'closing' => $this->settings()->identity_closing,
            'heroImage' => $this->heroUrl('mission'),
            'seoTitle' => $page?->seo_title ?: 'Mission, Vision, Pledge & Anthem',
            'seoDescription' => $page?->seo_description ?: $page?->excerpt,
        ]);
    }

    public function page(string $slug): View
    {
        $page = CmsPage::published()->with('featuredMedia')->where('slug', $slug)->firstOrFail();
        $mediaIds = collect($page->content_blocks ?? [])->pluck('media_id')->filter()->unique();

        return $this->render('site.page', [
            'page' => $page,
            'contentMedia' => MediaAsset::query()->whereIn('id', $mediaIds)->get()->keyBy('id'),
            'heroImage' => $this->heroUrl('page', $page->featuredMedia),
            'seoTitle' => $page->seo_title ?: $page->title,
            'seoDescription' => $page->seo_description ?: $page->excerpt,
        ]);
    }

    public function academics(): View
    {
        return $this->render('site.academics', [
            'programmes' => AcademicProgramme::query()->where('is_published', true)->with('image')->orderBy('sort_order')->get(),
            'heroImage' => $this->heroUrl('academics'),
            'seoDescription' => 'Explore Primary and Secondary education at Emmaculate Academy.',
        ]);
    }

    public function programme(string $slug): View
    {
        $programme = AcademicProgramme::query()->where('is_published', true)->where('slug', $slug)->with('image')->firstOrFail();

        return $this->render('site.programme', [
            'programme' => $programme,
            'heroImage' => $this->heroUrl('academics', $programme->image),
            'seoTitle' => $programme->seo_title ?: $programme->title,
            'seoDescription' => $programme->seo_description ?: $programme->intro,
        ]);
    }

    public function admissions(): View
    {
        $admissions = AdmissionsSettings::current()->load('image');
        $steps = SiteSections::items('admissions.steps');
        if ($steps === []) {
            // Fall back to the plain list of steps edited under Admissions settings.
            $steps = array_map(fn ($text) => ['icon' => 'checkCircle', 'title' => (string) $text, 'text' => ''], array_values($admissions->process_steps ?? []));
        }
        $levels = SiteSections::items('admissions.levels');
        $levelMedia = MediaAsset::query()->whereIn('id', collect($levels)->pluck('media_id')->filter()->all())->get()->keyBy('id');

        return $this->render('site.admissions', [
            'admissions' => $admissions,
            'steps' => $steps,
            'levels' => $levels,
            'levelMedia' => $levelMedia,
            'faqs' => Faq::query()->where('is_visible', true)->orderBy('sort_order')->limit(4)->get(),
            'heroImage' => $this->heroUrl('admissions', $admissions->image),
            'seoDescription' => 'Admission information for Emmaculate Academy. Contact the school for current availability.',
        ]);
    }

    public function leadership(): View
    {
        return $this->render('site.leadership', [
            'leaders' => LeadershipProfile::query()->where('is_visible', true)->with('photo')->orderBy('sort_order')->get(),
            'heroImage' => $this->heroUrl('leadership'),
        ]);
    }

    public function news(): View
    {
        return $this->render('site.news.index', [
            'posts' => NewsPost::published()->with('coverImage')->orderByDesc('published_at')->paginate(6),
            'events' => SchoolEvent::published()->upcoming()->orderByRaw('starts_at IS NULL')->orderBy('starts_at')->limit(3)->get(),
            'highlights' => $this->galleryPhotos(2),
            'heroImage' => $this->heroUrl('news'),
            'seoDescription' => 'Published news and updates from Emmaculate Academy.',
        ]);
    }

    public function newsShow(string $slug): View
    {
        $post = NewsPost::published()->with('coverImage', 'author')->where('slug', $slug)->firstOrFail();

        return $this->render('site.news.show', [
            'post' => $post,
            'related' => NewsPost::published()->with('coverImage')->where('id', '!=', $post->id)->orderByDesc('published_at')->limit(3)->get(),
            'heroImage' => $this->heroUrl('news'),
            'seoTitle' => $post->seo_title ?: $post->title,
            'seoDescription' => $post->seo_description ?: $post->excerpt,
        ]);
    }

    public function events(): View
    {
        return $this->render('site.events.index', [
            'events' => SchoolEvent::published()->with('image')->orderByRaw('starts_at IS NULL')->orderBy('starts_at')->paginate(9),
            'heroImage' => $this->heroUrl('events'),
        ]);
    }

    public function eventShow(string $slug): View
    {
        $event = SchoolEvent::published()->with('image')->where('slug', $slug)->firstOrFail();

        return $this->render('site.events.show', [
            'event' => $event,
            'heroImage' => $this->heroUrl('events'),
            'seoTitle' => $event->seo_title ?: $event->title,
            'seoDescription' => $event->seo_description ?: $event->description,
        ]);
    }

    public function announcements(): View
    {
        return $this->render('site.announcements', [
            'announcements' => Announcement::visibleNow()->with('image')->orderBy('sort_order')->orderByDesc('starts_at')->paginate(12),
            'heroImage' => $this->heroUrl('news'),
        ]);
    }

    public function gallery(): View
    {
        $built = SiteCache::remember('gallery', 300, function (): array {
            $albums = GalleryAlbum::query()->where('status', 'published')->with(['images.media'])->orderBy('sort_order')->get();
            $categories = [];
            $photos = [];

            foreach ($albums as $album) {
                $count = 0;
                foreach ($album->images as $image) {
                    $url = $image->media?->url() ?? ($image->image_path ? asset('storage/'.$image->image_path) : null);
                    if (! $url || count($photos) >= 150) {
                        continue;
                    }
                    $photos[] = [
                        'url' => $url,
                        'alt' => $image->alt_text ?: ($image->media?->alt_text ?: $album->title),
                        'caption' => $image->caption ?: $album->title,
                        'album' => $album->slug,
                        'albumTitle' => $album->title,
                    ];
                    $count++;
                }
                if ($count > 0) {
                    $categories[] = ['slug' => $album->slug, 'title' => $album->title, 'count' => $count];
                }
            }

            return ['categories' => $categories, 'photos' => $photos];
        });

        return $this->render('site.gallery', $built + [
            'heroImage' => $this->heroUrl('gallery'),
            'seoDescription' => 'Photographs from the Emmaculate Academy community.',
        ]);
    }

    public function faq(): View
    {
        return $this->render('site.faq', [
            'faqs' => Faq::query()->where('is_visible', true)->orderBy('sort_order')->get(),
            'heroImage' => $this->heroUrl('faq'),
        ]);
    }

    public function contact(Request $request): View
    {
        return $this->render('site.contact', [
            'heroImage' => $this->heroUrl('contact'),
            'prefillSubject' => Str::limit(trim(strip_tags((string) $request->query('subject', ''))), 160, ''),
            'seoDescription' => 'Contact Emmaculate Academy in Arigidi Akoko, Ondo State.',
        ]);
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

    public function subscribeNewsletter(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'newsletter_email' => ['required', 'email:rfc', 'max:190'],
            'website' => ['nullable', 'max:0'],
        ]);

        NewsletterSubscriber::query()->firstOrCreate(
            ['email' => Str::lower(trim($data['newsletter_email']))],
            [
                'status' => 'subscribed',
                'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
                'subscribed_at' => now(),
            ],
        );

        // The same message is shown whether or not the address was already subscribed.
        return back()->with('success', 'Thank you for subscribing. We will keep you updated.');
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
        $this->notifyApplication($application);

        return redirect()->route('admissions')->with('application_reference', $application->reference);
    }

    public function credit(): View
    {
        $credit = config('school.credit');

        return $this->render('site.designed-by', [
            'whatsappUrl' => 'https://wa.me/'.$credit['whatsapp'].'?text='.rawurlencode('Hello '.$credit['name'].', I saw the '.$this->settings()->school_name.' website and I would like to talk about a project.'),
            'websiteUrl' => $credit['website'],
            'creditName' => $credit['name'],
            'heroImage' => null,
        ]);
    }

    public function unsubscribe(NewsletterSubscriber $subscriber): View
    {
        $subscriber->forceFill(['status' => 'unsubscribed'])->save();

        return $this->render('site.message', [
            'heading' => 'You have been unsubscribed',
            'message' => 'You will no longer receive our newsletter emails. You can subscribe again at any time from the bottom of any page.',
        ]);
    }

    /** Emails the family a receipt for their application and tells the school office. Never blocks the form. */
    private function notifyApplication(AdmissionApplication $application): void
    {
        try {
            $school = $this->settings();
            if (filled($application->guardian_email)) {
                Mail::to($application->guardian_email)->send(new SchoolUpdateMail(
                    subjectLine: 'We received your application - '.$school->school_name,
                    heading: 'Application received',
                    bodyText: 'Thank you for applying to '.$school->school_name.'. We have received the application for '.$application->applicant_name."\n\nYour reference number is ".$application->reference.'. Please keep it. The school will contact you with the next steps.',
                    url: route('admissions'),
                    buttonLabel: 'Visit admissions page',
                ));
            }
            if (filled($school->email)) {
                Mail::to($school->email)->send(new SchoolUpdateMail(
                    subjectLine: 'New admission application: '.$application->applicant_name,
                    heading: 'New admission application',
                    bodyText: 'Applicant: '.$application->applicant_name."\nApplying for: ".$application->applying_for."\nParent/guardian: ".$application->guardian_name."\nPhone: ".$application->guardian_phone."\nReference: ".$application->reference,
                ));
            }
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    /**
     * Mission, vision, pledge and anthem written in Admin > School profile win;
     * anything still only in the old Mission & Vision page fills the gaps.
     *
     * @param  array<string, array{paragraphs: array<int, string>, items: array<int, string>}>  $fallback
     */
    private function identityGroups(array $fallback): array
    {
        $settings = $this->settings();
        $lines = fn (?string $text): array => array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $text) ?: []), fn ($line) => $line !== ''));
        $groups = [];
        if (filled($settings->mission)) {
            $groups['mission'] = ['paragraphs' => [trim((string) $settings->mission)], 'items' => []];
        }
        if (filled($settings->vision)) {
            $groups['vision'] = ['paragraphs' => [trim((string) $settings->vision)], 'items' => []];
        }
        if (filled($settings->pledge)) {
            $groups['pledge'] = ['paragraphs' => [], 'items' => $lines($settings->pledge)];
        }
        if (filled($settings->anthem)) {
            $groups['anthem'] = ['paragraphs' => [], 'items' => $lines($settings->anthem)];
        }

        return $groups + $fallback;
    }

    private function settings(): SchoolSettings
    {
        return SchoolSettings::current();
    }

    /**
     * Banner image for a page. Priority: the page's own featured media, then an admin override in
     * School settings > global settings (key "hero_<page>" holding a media-library ID or a storage path),
     * then the shipped default photograph.
     */
    private function heroUrl(string $key, ?MediaAsset $media = null): ?string
    {
        if ($media) {
            return $media->url();
        }

        $configured = data_get($this->settings()->global_settings, 'hero_'.$key);
        if (filled($configured)) {
            $configured = trim((string) $configured);
            if (ctype_digit($configured)) {
                $asset = MediaAsset::query()->find((int) $configured);
                if ($asset) {
                    return $asset->url();
                }
            } elseif (! str_contains($configured, '..')) {
                return asset('storage/'.ltrim($configured, '/'));
            }
        }

        $default = self::HERO_DEFAULTS[$key] ?? null;

        return $default ? asset('storage/'.$default) : null;
    }

    /** @return array<int, array{url: string, alt: string}> */
    private function galleryPhotos(int $limit): array
    {
        $albums = GalleryAlbum::query()->where('status', 'published')->with(['images.media'])->orderBy('sort_order')->get();
        $photos = [];
        foreach ($albums as $album) {
            foreach ($album->images as $image) {
                $url = $image->media?->url() ?? ($image->image_path ? asset('storage/'.$image->image_path) : null);
                if ($url) {
                    $photos[] = ['url' => $url, 'alt' => $image->alt_text ?: ($image->media?->alt_text ?: $album->title)];
                }
                if (count($photos) >= $limit) {
                    return $photos;
                }
            }
        }

        return $photos;
    }

    private function render(string $view, array $data = []): View
    {
        $base = [
            'settings' => $this->settings(),
            'navigation' => SiteCache::remember('navigation', 600, fn () => NavigationItem::query()->where('menu', 'main')->whereNull('parent_id')->where('is_visible', true)->with('children')->orderBy('sort_order')->get()),
            'footerSections' => SiteCache::remember('footer', 600, fn () => FooterSection::query()->where('is_visible', true)->orderBy('sort_order')->get()),
            'portalLinks' => SiteCache::remember('portal-links', 600, fn () => PortalLink::query()->publiclyVisible()->get()),
        ];

        return view($view, array_merge($base, $data));
    }
}
