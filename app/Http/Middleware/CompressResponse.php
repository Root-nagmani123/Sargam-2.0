<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Gzip text responses when the client supports it.
 *
 * The FC registration form steps render very repetitive HTML (thousands of <option>
 * tags across dozens of dropdowns) — a load test measured a 3.16 MB average response
 * on the Descriptive Roll step at ~5 MB/s, which saturated the connection long before
 * the database became the limit. This HTML compresses ~13x, so the same page costs
 * roughly 64 KB on the wire instead of 856 KB.
 *
 * A front-end web server (nginx/apache) will normally do this, but it is not applied
 * by `php artisan serve` and is easy to leave unconfigured on a deployment — so doing
 * it here guarantees the saving. If the web server already compressed the response,
 * Content-Encoding is set and this middleware leaves it alone.
 */
class CompressResponse
{
    /** Don't bother compressing responses smaller than this (bytes). */
    private const MIN_LENGTH = 1024;

    /** Content types worth compressing. */
    private const COMPRESSIBLE = [
        'text/html',
        'text/plain',
        'text/css',
        'text/xml',
        'application/json',
        'application/javascript',
        'application/xml',
        'image/svg+xml',
    ];

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (! $this->shouldCompress($request, $response)) {
            return $response;
        }

        $content = $response->getContent();
        if ($content === false || $content === null || strlen($content) < self::MIN_LENGTH) {
            return $response;
        }

        $encoded = gzencode($content, 6);
        if ($encoded === false || strlen($encoded) >= strlen($content)) {
            return $response;
        }

        $response->setContent($encoded);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Content-Length', (string) strlen($encoded));

        // Caches must key on Accept-Encoding or they may serve gzip to a client
        // that cannot decode it.
        $vary = $response->headers->get('Vary');
        if ($vary === null || stripos($vary, 'accept-encoding') === false) {
            $response->headers->set('Vary', trim(($vary ? $vary.', ' : '').'Accept-Encoding', ', '));
        }

        return $response;
    }

    private function shouldCompress(Request $request, $response): bool
    {
        if (! $response instanceof Response) {
            return false;
        }

        // Streamed and file responses have no in-memory body to compress.
        if ($response instanceof StreamedResponse || $response instanceof BinaryFileResponse) {
            return false;
        }

        if (! function_exists('gzencode')) {
            return false;
        }

        // Already encoded (by the web server, or another middleware).
        if ($response->headers->has('Content-Encoding')) {
            return false;
        }

        // PHP is already gzipping via zlib.output_compression — don't double-encode.
        if (filter_var(ini_get('zlib.output_compression'), FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        if (! str_contains(strtolower((string) $request->headers->get('Accept-Encoding', '')), 'gzip')) {
            return false;
        }

        // 204/304 and friends carry no body.
        if ($response->isEmpty() || $response->isRedirection()) {
            return false;
        }

        $contentType = strtolower((string) $response->headers->get('Content-Type', ''));
        if ($contentType === '') {
            return false;
        }

        foreach (self::COMPRESSIBLE as $type) {
            if (str_starts_with($contentType, $type)) {
                return true;
            }
        }

        return false;
    }
}
