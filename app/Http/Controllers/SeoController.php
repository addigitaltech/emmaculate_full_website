<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;
use App\Models\NewsPost;
use App\Models\SchoolEvent;
use App\Models\SchoolSettings;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;
use XMLWriter;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $base = rtrim(SchoolSettings::current()->canonical_base_url ?: URL::to('/'), '/');
        $urls = [
            ['loc' => $base.'/', 'lastmod' => now()->toAtomString()],
            ['loc' => $base.'/academics', 'lastmod' => now()->toAtomString()],
            ['loc' => $base.'/admissions', 'lastmod' => now()->toAtomString()],
            ['loc' => $base.'/leadership', 'lastmod' => now()->toAtomString()],
            ['loc' => $base.'/news', 'lastmod' => now()->toAtomString()],
            ['loc' => $base.'/events', 'lastmod' => now()->toAtomString()],
            ['loc' => $base.'/announcements', 'lastmod' => now()->toAtomString()],
            ['loc' => $base.'/gallery', 'lastmod' => now()->toAtomString()],
            ['loc' => $base.'/faq', 'lastmod' => now()->toAtomString()],
            ['loc' => $base.'/contact', 'lastmod' => now()->toAtomString()],
        ];
        foreach (CmsPage::published()->get(['slug', 'updated_at']) as $page) {
            $urls[] = ['loc' => $base.'/'.$page->slug, 'lastmod' => $page->updated_at?->toAtomString()];
        }
        foreach (NewsPost::published()->get(['slug', 'updated_at']) as $post) {
            $urls[] = ['loc' => $base.'/news/'.$post->slug, 'lastmod' => $post->updated_at?->toAtomString()];
        }
        foreach (SchoolEvent::published()->get(['slug', 'updated_at']) as $event) {
            $urls[] = ['loc' => $base.'/events/'.$event->slug, 'lastmod' => $event->updated_at?->toAtomString()];
        }
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        foreach ($urls as $item) {
            $xml->startElement('url');
            $xml->writeElement('loc', $item['loc']);
            if ($item['lastmod']) {
                $xml->writeElement('lastmod', $item['lastmod']);
            }
            $xml->endElement();
        }
        $xml->endElement();
        $xml->endDocument();
        return response($xml->outputMemory(), 200, ['Content-Type' => 'application/xml; charset=UTF-8', 'Cache-Control' => 'public, max-age=300']);
    }

    public function robots(): Response
    {
        $base = rtrim(SchoolSettings::current()->canonical_base_url ?: URL::to('/'), '/');
        return response("User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /portal\nDisallow: /login\nDisallow: /forgot-password\nSitemap: {$base}/sitemap.xml\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8', 'Cache-Control' => 'public, max-age=300']);
    }
}
