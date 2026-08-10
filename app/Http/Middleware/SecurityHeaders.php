<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds security-related HTTP response headers.
 *
 * Every header here is additive and behaviour-preserving — it hardens how the
 * browser treats the response without changing application logic, routes or
 * markup. Addresses the L1 VAPT findings: CSP missing, HSTS missing/disabled,
 * X-Frame-Options missing, X-Content-Type-Options missing, improper/duplicate
 * Cache-Control, and Back-and-Refresh caching of authenticated pages.
 *
 * Note on CSP: scripts are restricted to 'self' + the specific CDNs and the
 * Google-Translate loaders the UI actually uses — the previous bare `https:`
 * wildcard (which let ANY https origin serve executable script) has been
 * removed. `'unsafe-inline'`/`'unsafe-eval'` are still allowed for scripts and
 * styles because ~350 inline event handlers, ~374 inline <script> blocks and
 * ~2,983 inline style="" attributes depend on them; dropping those needs a
 * per-request nonce and a markup refactor tracked as a separate item. Non-script
 * resource types stay permissive (https:) to avoid breaking the many image/font
 * CDNs, and the high-value directives (frame-ancestors, object-src, base-uri)
 * remain enforced.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $next($request);
        $headers  = $response->headers;

        // Clickjacking protection (do not override a stricter value a route may set).
        if (! $headers->has('X-Frame-Options')) {
            $headers->set('X-Frame-Options', 'SAMEORIGIN');
        }

        // Stop MIME-sniffing (uploads, reflected content).
        $headers->set('X-Content-Type-Options', 'nosniff');

        if (! $headers->has('Referrer-Policy')) {
            $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        }

        if (! $headers->has('Permissions-Policy')) {
            $headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        }

        // Content Security Policy. script-src is an explicit allowlist (no bare
        // `https:` wildcard, so a stray CDN or injected <script src> off any
        // origin can't run); other resource types stay permissive so the inline
        // styles / image + font CDNs the UI already uses keep working.
        if (! $headers->has('Content-Security-Policy')) {
            $scriptHosts = [
                "'self'", "'unsafe-inline'", "'unsafe-eval'", 'blob:',
                'https://cdn.jsdelivr.net',
                'https://cdn.datatables.net',
                'https://code.jquery.com',
                'https://cdnjs.cloudflare.com',
                // Google Translate widget loads these at runtime (see google-translate.js).
                'https://translate.google.com',
                'https://translate.googleapis.com',
                'https://www.gstatic.com',
            ];

            $headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self' https: data: blob:",
                'script-src ' . implode(' ', $scriptHosts),
                // Inline style="" attributes (~2,983 of them) require 'unsafe-inline'.
                "style-src 'self' 'unsafe-inline' https:",
                "img-src 'self' data: blob: https:",
                "font-src 'self' data: https:",
                "connect-src 'self' https:",
                "frame-src 'self' https:",
                "frame-ancestors 'self'",
                "base-uri 'self'",
                "object-src 'none'",
            ]));
        }

        // HSTS only over HTTPS (browsers ignore it on plain HTTP; omitting it
        // there keeps local http:// development working).
        if ($request->isSecure() && ! $headers->has('Strict-Transport-Security')) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Prevent caching of authenticated / dynamic HTML so sensitive pages are
        // not restored via Back / Forward / Refresh, and emit a single consistent
        // Cache-Control. Also covers generated file downloads (Excel/PDF exports
        // sent as attachments): these are built per-request from the current
        // filters, so a browser/proxy-cached copy re-serves a stale export (e.g.
        // last month's Sale Voucher rows) even though the live HTML report is
        // correct. JSON APIs and static assets are untouched; any explicit
        // public/max-age caching a route sets is preserved.
        $contentType = (string) $headers->get('Content-Type');
        $disposition = strtolower((string) $headers->get('Content-Disposition'));
        $isAttachment = strpos($disposition, 'attachment') !== false;
        $existing = strtolower((string) $headers->get('Cache-Control'));

        if (stripos($contentType, 'text/html') !== false) {
            if ($existing === '' || (strpos($existing, 'max-age') === false && strpos($existing, 'public') === false)) {
                $headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
                $headers->set('Pragma', 'no-cache');
            }
        } elseif ($isAttachment) {
            // Generated exports (BinaryFileResponse) default to "Cache-Control:
            // public"; that lets the browser/proxy re-serve a stale download for
            // the same URL. Force no-store unless the route deliberately opted
            // into caching via an explicit max-age (bare "public" is only the
            // framework default, not an intentional caching choice).
            if (strpos($existing, 'max-age') === false) {
                $headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
                $headers->set('Pragma', 'no-cache');
            }
        }

        return $response;
    }
}
