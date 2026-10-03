<?php

namespace App\Domain\Website\Support;

final class SafePublicUrl
{
    public static function allows(?string $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        $url = trim($value);
        if ($url === '' || preg_match('/[\x00-\x20\\\\]/', $url)) {
            return false;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return true;
        }

        $parts = parse_url($url);
        return is_array($parts)
            && strtolower((string) ($parts['scheme'] ?? '')) === 'https'
            && ! empty($parts['host'])
            && ! isset($parts['user'])
            && ! isset($parts['pass']);
    }
}
