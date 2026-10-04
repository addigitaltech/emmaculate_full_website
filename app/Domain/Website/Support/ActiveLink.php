<?php

namespace App\Domain\Website\Support;

/**
 * Decides which navigation entry is "current" for the visible URL path.
 */
final class ActiveLink
{
    public static function matches(?string $url, string $currentPath): bool
    {
        if ($url === null || $url === '') {
            return false;
        }

        $target = trim((string) parse_url($url, PHP_URL_PATH), '/');
        $current = trim($currentPath, '/');

        if ($target === '') {
            return $current === '';
        }

        return $current === $target || str_starts_with($current, $target.'/');
    }

    /** True when the item, or any of its (already loaded) children, matches the current path. */
    public static function item(object $item, string $currentPath): bool
    {
        if (self::matches($item->url ?? null, $currentPath)) {
            return true;
        }

        foreach (($item->children ?? []) as $child) {
            if (self::matches($child->url ?? null, $currentPath)) {
                return true;
            }
        }

        return false;
    }
}
