<?php

namespace App\Http\Middleware;

use App\Support\MediaUrl;
use Closure;
use Illuminate\Http\Request;

/** Makes Storage::disk('public')->url() (used by admin upload previews) match the address the site is opened on. */
class ConfigureMediaUrl
{
    public function handle(Request $request, Closure $next)
    {
        config(['filesystems.disks.public.url' => MediaUrl::base()]);

        return $next($request);
    }
}
