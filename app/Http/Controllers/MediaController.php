<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Serves uploaded pictures when the `public/storage` link is not available (see App\Support\MediaUrl). */
class MediaController extends Controller
{
    private const TYPES = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif',
        'webp' => 'image/webp', 'avif' => 'image/avif', 'ico' => 'image/x-icon', 'svg' => 'image/svg+xml',
        'pdf' => 'application/pdf',
    ];

    public function show(string $path): BinaryFileResponse|RedirectResponse
    {
        $path = trim($path, '/');
        // Only plain relative paths inside the public disk: no "..", no hidden files, no odd characters.
        abort_if($path === '' || strlen($path) > 300 || preg_match('#(^|/)\.|\\\\|\0|//#', $path) || str_contains($path, '..'), 404);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        abort_unless(isset(self::TYPES[$extension]), 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);

        try {
            $full = $disk->path($path);
        } catch (\Throwable) {
            return redirect()->away($disk->url($path)); // a cloud disk: let it serve the file itself
        }
        $root = realpath(storage_path('app/public'));
        $real = realpath($full);
        abort_unless($root && $real && str_starts_with($real, $root.DIRECTORY_SEPARATOR) && is_file($real), 404);

        $headers = [
            'Content-Type' => self::TYPES[$extension],
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ];
        if ($extension === 'svg') {
            // A picture must never run scripts.
            $headers['Content-Security-Policy'] = "default-src 'none'; style-src 'unsafe-inline'; sandbox";
        }

        return response()->file($real, $headers);
    }
}
