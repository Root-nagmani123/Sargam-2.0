<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Shared DataTables server-side JSON caching (Redis / Laravel cache store via RedisBackedCache).
 * New listings: call {@see self::serveCachedAjax()} from ajax() and {@see self::bumpListEpoch()} after mutations.
 */
final class DataTableRedisCache
{
    /**
     * @param  array{enabled: string, seconds: string}  $envKeys  Full .env key names, e.g. MEMBER_DATATABLE_CACHE_ENABLED
     * @param  callable(): JsonResponse  $parentAjax  Typically `fn () => parent::ajax()`
     */
    public static function serveCachedAjax(
        Request $request,
        string $v1KeyPrefix,
        string $listEpochKey,
        array $envKeys,
        string $logLabel,
        callable $parentAjax
    ): JsonResponse {
        $draw = (int) $request->input('draw', 0);
        $fingerprint = self::requestFingerprint($request, self::readListEpoch($listEpochKey));
        $cacheKey = $v1KeyPrefix . md5(json_encode($fingerprint));

        $payload = self::remember(
            $cacheKey,
            $envKeys,
            $logLabel,
            function () use ($parentAjax) {
                $resp = $parentAjax();
                $data = $resp->getData(true);
                if (! is_array($data)) {
                    return ['__passthrough' => true, 'body' => $resp->getContent()];
                }
                unset($data['draw']);

                return $data;
            }
        );

        if (is_array($payload) && ! isset($payload['__passthrough'])) {
            $payload = self::refreshCsrfInDataTablePayload($payload);
        }

        if (isset($payload['__passthrough']) && $payload['__passthrough']) {
            $decoded = json_decode((string) ($payload['body'] ?? ''), true);
            if (! is_array($decoded)) {
                return $parentAjax();
            }
            $decoded = self::refreshCsrfInDataTablePayload($decoded);

            return new JsonResponse(array_merge($decoded, ['draw' => $draw]));
        }

        $payload['draw'] = $draw;

        return new JsonResponse($payload);
    }

    public static function bumpListEpoch(string $listEpochKey, string $logLabel = 'DataTable'): void
    {
        try {
            $storeName = RedisBackedCache::projectDefaultStoreName();
            $repo = RedisBackedCache::repositoryForStore($storeName);
            $repo->increment($listEpochKey);
        } catch (\Throwable $e) {
            Log::warning("{$logLabel}: failed to bump list cache epoch.", [
                'message' => $e->getMessage(),
            ]);
        }
    }

    public static function readListEpoch(string $listEpochKey): int
    {
        try {
            $storeName = RedisBackedCache::projectDefaultStoreName();
            $repo = RedisBackedCache::repositoryForStore($storeName);

            return (int) $repo->get($listEpochKey, 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function requestFingerprint(Request $r, int $epoch): array
    {
        $columns = $r->input('columns', []);
        $colSearch = [];
        if (is_array($columns)) {
            foreach ($columns as $c) {
                if (! is_array($c)) {
                    continue;
                }
                $colSearch[] = [
                    'data' => $c['data'] ?? '',
                    'sv' => trim((string) data_get($c, 'search.value', '')),
                ];
            }
        }

        return [
            'epoch' => $epoch,
            'start' => (int) $r->input('start', 0),
            'len' => $r->input('length', 10),
            'q' => trim((string) data_get($r->all(), 'search.value', '')),
            'order' => $r->input('order', []),
            'cols' => $colSearch,
        ];
    }

    /**
     * @param  array{enabled: string, seconds: string}  $envKeys
     * @param  callable(): mixed  $callback
     * @return mixed
     */
    public static function remember(string $cacheKey, array $envKeys, string $logLabel, callable $callback)
    {
        $enabled = ! in_array(strtolower((string) env($envKeys['enabled'], 'true')), ['0', 'false', 'no', 'off'], true);
        $ttl = max(30, (int) env($envKeys['seconds'], 300));
        $storeName = RedisBackedCache::projectDefaultStoreName();
        $repository = RedisBackedCache::repositoryForStore($storeName);
        if (! $enabled) {
            return $callback();
        }
        try {
            return $repository->remember($cacheKey, $ttl, $callback);
        } catch (\Throwable $e) {
            Log::warning("{$logLabel}: cache store failed, using DB only.", [
                'store' => $storeName,
                'message' => $e->getMessage(),
            ]);

            return $callback();
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function refreshCsrfInDataTablePayload(array $payload): array
    {
        $token = csrf_token();
        if ($token === '' || ! isset($payload['data']) || ! is_array($payload['data'])) {
            return $payload;
        }
        $replacement = 'name="_token" value="' . e($token) . '"';
        foreach ($payload['data'] as $i => $row) {
            if (! is_array($row)) {
                continue;
            }
            foreach ($row as $key => $val) {
                if (! is_string($val) || ! str_contains($val, 'name="_token"')) {
                    continue;
                }
                $payload['data'][$i][$key] = preg_replace(
                    '/name="_token" value="[^"]*"/',
                    $replacement,
                    $val
                ) ?? $val;
            }
        }

        return $payload;
    }
}
