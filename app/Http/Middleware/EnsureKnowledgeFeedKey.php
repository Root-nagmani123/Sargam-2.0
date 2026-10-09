<?php

namespace App\Http\Middleware;

use App\Support\LogSafe;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Guards the ज्ञानकोश pull feed (config/knowledge.php → feed).
 *
 * The caller sends `Authorization: Bearer <key>`; only the key's SHA-256 is in
 * config, so a leaked .env or config dump does not leak a usable key. Answers
 * 404 while the feed is off, so its existence is not advertised.
 */
class EnsureKnowledgeFeedKey
{
    public function handle(Request $request, Closure $next)
    {
        if (! config('knowledge.feed.enabled')) {
            abort(404);
        }

        $expected = strtolower(trim((string) config('knowledge.feed.key_sha256')));
        $token = (string) $request->bearerToken();

        if ($expected === '' || $token === '' || ! hash_equals($expected, hash('sha256', $token))) {
            Log::warning('Knowledge feed: rejected request — key missing or wrong', LogSafe::context([
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]));

            return response()->json(['error' => 'unauthorized'], 401);
        }

        $allowedIps = config('knowledge.feed.allowed_ips', []);
        if ($allowedIps !== [] && ! in_array($request->ip(), $allowedIps, true)) {
            Log::warning('Knowledge feed: rejected request — IP not allowed', LogSafe::context([
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]));

            return response()->json(['error' => 'forbidden'], 403);
        }

        return $next($request);
    }
}
