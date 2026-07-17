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
 * Note on CSP: the policy is intentionally permissive for scripts/styles
 * because the existing UI relies on inline JS/CSS and CDN assets. It still
 * enforces the high-value directives (frame-ancestors, object-src, base-uri).
 * Tighten default-src/script-src to 'self' only after auditing inline usage.
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

        // Content Security Policy — permissive default so the current inline-JS /
        // CDN UI keeps working, with the security-critical directives enforced.
        if (! $headers->has('Content-Security-Policy')) {
            $headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self' https: data: blob: 'unsafe-inline' 'unsafe-eval'",
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
        // Cache-Control. Limited to HTML responses so JSON APIs, file downloads
        // and static assets are untouched; any explicit public/max-age caching a
        // route sets is preserved.
        $contentType = (string) $headers->get('Content-Type');
        if (stripos($contentType, 'text/html') !== false) {
            $existing = strtolower((string) $headers->get('Cache-Control'));
            if ($existing === '' || (strpos($existing, 'max-age') === false && strpos($existing, 'public') === false)) {
                $headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
                $headers->set('Pragma', 'no-cache');
            }
        }

        return $response;
    }
}
