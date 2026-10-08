<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Short-lived cache for the data every public page needs (settings, menus, footer, home page content).
 * Saving or deleting any website content bumps a version number, so editors always see their change
 * straight away while visitors are served from the cache instead of the database.
 */
final class SiteCache
{
    private const VERSION_KEY = 'site.cache.version';

    public static function remember(string $key, int $seconds, Closure $callback): mixed
    {
        return Cache::remember('site.v'.self::version().'.'.$key, $seconds, $callback);
    }

    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }

    private static function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }
}
