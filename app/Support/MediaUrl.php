<?php

namespace App\Support;

/**
 * The address of a picture or file the school uploaded. It works on every kind of hosting:
 *  - when the `public/storage` link exists (normal hosting) the file is served straight from there, which is fastest;
 *  - when it does not (XAMPP and many cheap hosts cannot make the link) the file is served through /media/..., so
 *    pictures never break just because `php artisan storage:link` was not run or APP_URL does not match the address
 *    the site is opened on.
 */
final class MediaUrl
{
    public static function base(): string
    {
        return is_dir(public_path('storage')) ? rtrim(asset('storage'), '/') : rtrim(url('media'), '/');
    }

    public static function for(?string $path): ?string
    {
        $path = trim((string) $path, '/');
        if ($path === '') {
            return null;
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        return self::base().'/'.implode('/', array_map('rawurlencode', explode('/', $path)));
    }
}
