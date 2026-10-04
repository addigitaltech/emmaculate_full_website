<?php

namespace App\Domain\Website\Support;

/**
 * Turns the CMS page "content blocks" into structures the designed pages need,
 * so editors keep using the normal page editor.
 */
final class PageBlocks
{
    /**
     * Split blocks into chapters. A "section" block starts a new chapter; a heading followed by
     * a list becomes a roster box; a list with no heading becomes bullets; a quote is the closing note.
     *
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<int, array<string, mixed>>
     */
    public static function chapters(array $blocks): array
    {
        $chapters = [];
        $current = null;
        $heading = null;

        foreach ($blocks as $block) {
            if (! is_array($block)) {
                continue;
            }
            $type = $block['type'] ?? '';

            if ($type === 'section') {
                if ($current !== null) {
                    $chapters[] = $current;
                }
                $current = self::blank((string) ($block['text'] ?? ''));
                $heading = null;
                continue;
            }

            if ($current === null) {
                $current = self::blank('');
            }

            if ($type === 'heading') {
                $heading = trim((string) ($block['text'] ?? ''));
            } elseif ($type === 'paragraph') {
                $text = trim((string) ($block['text'] ?? ''));
                if ($text !== '') {
                    $current['paragraphs'][] = $text;
                }
            } elseif ($type === 'list') {
                $items = self::lines($block);
                if ($heading !== null && $heading !== '') {
                    $current['rosters'][] = ['title' => $heading, 'items' => $items];
                    $heading = null;
                } else {
                    $current['bullets'] = array_merge($current['bullets'], $items);
                }
            } elseif ($type === 'quote') {
                $current['quote'] = trim((string) ($block['text'] ?? ''));
            } elseif ($type === 'image') {
                $current['media_id'] = $block['media_id'] ?? null;
            }
        }

        if ($current !== null) {
            $chapters[] = $current;
        }

        return $chapters;
    }

    /**
     * Group blocks under their heading ("Our Mission" becomes the key "mission").
     *
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<string, array{paragraphs: array<int, string>, items: array<int, string>}>
     */
    public static function keyed(array $blocks): array
    {
        $groups = [];
        $key = null;

        foreach ($blocks as $block) {
            if (! is_array($block)) {
                continue;
            }
            $type = $block['type'] ?? '';

            if ($type === 'heading') {
                $key = preg_replace('/^our\s+/', '', strtolower(trim((string) ($block['text'] ?? ''))));
                $groups[$key] ??= ['paragraphs' => [], 'items' => []];
            } elseif ($key !== null && $type === 'paragraph') {
                $text = trim((string) ($block['text'] ?? ''));
                if ($text !== '') {
                    $groups[$key]['paragraphs'][] = $text;
                }
            } elseif ($key !== null && $type === 'list') {
                $groups[$key]['items'] = array_merge($groups[$key]['items'], self::lines($block));
            }
        }

        return $groups;
    }

    /** @return array<int, string> */
    public static function lines(array $block): array
    {
        $items = is_array($block['items'] ?? null)
            ? $block['items']
            : preg_split('/\R/', trim((string) ($block['text'] ?? '')));

        return array_values(array_filter(array_map(fn ($item) => trim((string) $item), $items ?: []), fn ($item) => $item !== ''));
    }

    /** First paragraphs of a page, used for short intros on the home page. */
    public static function paragraphs(array $blocks, int $limit = 2): array
    {
        $found = [];
        foreach ($blocks as $block) {
            if (is_array($block) && ($block['type'] ?? '') === 'paragraph' && trim((string) ($block['text'] ?? '')) !== '') {
                $found[] = trim((string) $block['text']);
                if (count($found) >= $limit) {
                    break;
                }
            }
        }

        return $found;
    }

    private static function blank(string $title): array
    {
        return ['title' => trim($title), 'paragraphs' => [], 'rosters' => [], 'bullets' => [], 'quote' => null, 'media_id' => null];
    }
}
