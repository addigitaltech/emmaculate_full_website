<?php

namespace App\Domain\Website\Support;

use App\Models\CmsSection;

/**
 * Editable, structured page sections (icon + title + text lists) managed in
 * Admin > Website > Page sections. Views read them through items().
 */
final class SiteSections
{
    public const KEYS = [
        'about.values' => 'About page: Our core values',
        'about.why' => 'About page: Why choose us',
        'academics.why' => 'Academics page: Why our academics stand out',
        'academics.curriculum' => 'Academics page: Curriculum overview',
        'academics.approach' => 'Academics page: Our learning approach',
        'admissions.steps' => 'Admissions page: Admission process steps',
        'admissions.levels' => 'Admissions page: Academic levels',
        'cta.admissions' => 'Banner: "Give your child a stronger foundation" (About, History)',
        'cta.join' => 'Banner: "Ready to join our family?" (Admissions)',
        'cta.academics' => 'Banner: "Empowering minds" (Academics)',
        'mission.closing' => 'Mission page: closing quote',
    ];

    public const ICONS = [
        'shield' => 'Shield',
        'book' => 'Open book',
        'bookText' => 'Book with text',
        'handshake' => 'Handshake',
        'lightbulb' => 'Light bulb',
        'handHeart' => 'Hand and heart',
        'heart' => 'Heart',
        'globe' => 'Globe',
        'users' => 'People',
        'user' => 'Person',
        'userPlus' => 'Add person',
        'cap' => 'Graduation cap',
        'award' => 'Award',
        'trophy' => 'Trophy',
        'medal' => 'Medal',
        'flask' => 'Science flask',
        'monitor' => 'Computer',
        'building' => 'Building',
        'school' => 'School',
        'trendingUp' => 'Results chart',
        'fileText' => 'Document',
        'folder' => 'Folder',
        'clipboard' => 'Clipboard',
        'send' => 'Message',
        'checkCircle' => 'Tick',
        'star' => 'Star',
        'target' => 'Target',
        'headset' => 'Support',
    ];

    private static ?array $cache = null;

    public static function get(string $key): ?CmsSection
    {
        if (self::$cache === null) {
            self::$cache = \App\Support\SiteCache::remember('sections', 600, fn () => CmsSection::query()
                ->whereNull('page_id')
                ->where('is_visible', true)
                ->orderBy('sort_order')
                ->get()
                ->keyBy('section_key')
                ->all());
        }

        return self::$cache[$key] ?? null;
    }

    /** @return array<int, array<string, mixed>> */
    public static function items(string $key): array
    {
        $section = self::get($key);
        $content = $section?->content;

        return is_array($content) ? array_values(array_filter($content, 'is_array')) : [];
    }

    public static function title(string $key, string $fallback): string
    {
        $title = self::get($key)?->title;

        return filled($title) ? (string) $title : $fallback;
    }

    public static function description(string $key, ?string $fallback = null): ?string
    {
        $text = self::get($key)?->description;

        return filled($text) ? (string) $text : $fallback;
    }
}
