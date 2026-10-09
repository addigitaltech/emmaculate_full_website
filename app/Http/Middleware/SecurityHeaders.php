<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $isAdminPanel = $request->is('admin') || $request->is('admin/*');
        $nonce = $isAdminPanel ? Vite::useCspNonce() : null;

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $scriptSrc = "'self'";
        $styleSrc = "'self' 'unsafe-inline' https://fonts.googleapis.com";
        $fontSrc = "'self' data: https://fonts.gstatic.com";
        $workerSrc = "'self'";

        if ($isAdminPanel && $nonce !== null) {
            $contentType = (string) $response->headers->get('Content-Type', '');
            if (str_contains(strtolower($contentType), 'text/html')) {
                $content = $response->getContent();
                if (is_string($content)) {
                    $content = preg_replace_callback('/<script\b([^>]*)>/i', static function (array $matches) use ($nonce): string {
                        $attributes = $matches[1];
                        if (preg_match('/\bsrc\s*=/i', $attributes) || preg_match('/\bnonce\s*=/i', $attributes)) {
                            return $matches[0];
                        }

                        return '<script nonce="'.$nonce.'"'.$attributes.'>';
                    }, $content) ?? $content;
                    $response->setContent($content);
                    $response->headers->remove('Content-Length');
                }
            }

            // Filament v3's Alpine expressions require eval; confine this allowance to the admin UI.
            $scriptSrc .= " 'nonce-{$nonce}' 'unsafe-eval'";
            $styleSrc .= ' https://fonts.bunny.net';
            $fontSrc .= ' https://fonts.bunny.net';
            $workerSrc .= ' blob:';
        }

        $response->headers->set('Content-Security-Policy', "default-src 'self'; base-uri 'self'; object-src 'none'; img-src 'self' data: blob: https:; worker-src {$workerSrc}; script-src {$scriptSrc}; style-src {$styleSrc}; font-src {$fontSrc}; connect-src 'self'; form-action 'self' https:");
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        return $response;
    }
}
