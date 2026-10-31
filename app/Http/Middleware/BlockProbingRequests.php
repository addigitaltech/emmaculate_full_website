<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Drops the requests bots send while hunting for weak spots (WordPress and phpMyAdmin paths, .env and .git files,
 * script uploads, directory tricks, absurdly long addresses) before they reach the application. Real visitors never
 * ask for these addresses, so nothing legitimate is blocked, and the logs stay readable.
 */
class BlockProbingRequests
{
    private const SEGMENTS = [
        'wp-login.php', 'wp-admin', 'wp-content', 'wp-includes', 'xmlrpc.php', 'phpmyadmin', 'pma', 'myadmin', 'adminer',
        'cgi-bin', 'boaform', 'eval-stdin.php', 'vendor', 'node_modules', 'telescope', 'actuator', 'solr', 'owa', 'autodiscover',
    ];

    public function handle(Request $request, Closure $next)
    {
        $uri = $request->getRequestUri();
        $path = strtolower(rawurldecode($request->getPathInfo()));

        abort_if(strlen($uri) > 2500, 414);
        abort_if(str_contains($uri, "\0") || str_contains($path, "\0") || str_contains($path, '../') || str_contains($path, '..\\'), 400);

        $blocked = (bool) preg_match('#(^|/)\.(env|git|svn|hg|ht|aws|ssh|docker|idea|vscode)#', $path)
            || (bool) preg_match('#\.(php\d?|phtml|phar|sql|bak|old|swp|ini|log|sh|asp|aspx|jsp|cgi)$#', $path) && $path !== '/index.php'
            || (bool) preg_match('#(^|/)(composer\.(json|lock)|package(-lock)?\.json|artisan|phpunit\.xml|dockerfile)$#', $path)
            || collect(explode('/', trim($path, '/')))->contains(fn ($segment) => in_array($segment, self::SEGMENTS, true));
        abort_if($blocked, 404);

        return $next($request);
    }
}
