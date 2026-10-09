<?php

namespace App\Services\Knowledge;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Thin HTTP client for the ज्ञानकोश ingest API (config/knowledge.php).
 *
 * Every method returns the same array shape instead of throwing, so a caller
 * can record the outcome whatever happened:
 *
 *   ok         bool         the API answered 200
 *   http       int|null     HTTP status, null when no response arrived
 *   status     string|null  the API's own `status` field (created, superseded, …)
 *   body       array        decoded JSON body
 *   error      string|null  short reason when !ok
 *   retryable  bool         5xx or no response — safe to send again unchanged
 */
class KnowledgeIngestClient
{
    public function enabled(): bool
    {
        return (bool) config('knowledge.enabled');
    }

    public function driver(): string
    {
        return strtolower((string) config('knowledge.driver', 'log'));
    }

    public function isConfigured(): bool
    {
        return match ($this->driver()) {
            'log' => true,
            'http' => filled(config('knowledge.base_url')) && filled(config('knowledge.key')),
            default => false,
        };
    }

    /**
     * POST /ingest — one document, multipart.
     *
     * @param  array<string, string>  $fields  form fields except external_source and file
     */
    public function ingest(array $fields, string $absolutePath, string $filename): array
    {
        $fields = ['external_source' => config('knowledge.external_source')] + $fields;

        if ($this->driver() === 'log') {
            Log::info('Knowledge ingest [log driver]: ingest', $fields + ['file' => $filename, 'bytes' => @filesize($absolutePath)]);

            return $this->fakeOk([
                'status' => 'created',
                'document_id' => 'log-' . substr(sha1($fields['external_id'] . '|' . $fields['external_version']), 0, 16),
                'external_id' => $fields['external_id'],
                'external_version' => $fields['external_version'],
                'replaces' => null,
                'needs_review' => true,
            ]);
        }

        $handle = @fopen($absolutePath, 'r');
        if ($handle === false) {
            return $this->failure(null, 'file not readable', false);
        }

        try {
            return $this->send(fn (PendingRequest $http) => $http
                ->attach('file', $handle, $filename)
                ->post($this->url('/ingest'), $fields));
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }
    }

    /** POST /ingest/withdraw — stop quoting a document. */
    public function withdraw(string $externalId, string $reason): array
    {
        $payload = [
            'external_source' => config('knowledge.external_source'),
            'external_id' => $externalId,
            'reason' => $reason,
        ];

        if ($this->driver() === 'log') {
            Log::info('Knowledge ingest [log driver]: withdraw', $payload);

            return $this->fakeOk(['status' => 'withdrawn']);
        }

        return $this->send(fn (PendingRequest $http) => $http->asJson()->post($this->url('/ingest/withdraw'), $payload));
    }

    /**
     * POST /ingest/reconcile — the full list of what is published here; anything
     * else they hold from "sargam" is withdrawn. 409 = their shrinkage guard fired
     * (our list is under half of theirs) and nothing was changed.
     *
     * @param  array<int, string>  $externalIds
     */
    public function reconcile(array $externalIds, bool $dryRun): array
    {
        $payload = [
            'external_source' => config('knowledge.external_source'),
            'external_ids' => array_values($externalIds),
            'dry_run' => $dryRun,
        ];

        if ($this->driver() === 'log') {
            Log::info('Knowledge ingest [log driver]: reconcile', [
                'count' => count($externalIds),
                'dry_run' => $dryRun,
            ]);

            return $this->fakeOk(['status' => $dryRun ? 'dry_run' : 'reconciled']);
        }

        return $this->send(fn (PendingRequest $http) => $http->asJson()->post($this->url('/ingest/reconcile'), $payload));
    }

    private function send(callable $call): array
    {
        if (! $this->isConfigured()) {
            return $this->failure(null, 'knowledge ingest is not configured (driver ' . $this->driver() . ')', false);
        }

        try {
            /** @var Response $response */
            $response = $call($this->http());
        } catch (ConnectionException $e) {
            // Timeout or no route: genuinely ambiguous, and a resend is idempotent.
            return $this->failure(null, 'no response: ' . $e->getMessage(), true);
        } catch (Throwable $e) {
            return $this->failure(null, get_class($e) . ': ' . $e->getMessage(), true);
        }

        $body = $response->json();
        $body = is_array($body) ? $body : [];

        if ($response->status() === 200) {
            return [
                'ok' => true,
                'http' => 200,
                'status' => isset($body['status']) ? (string) $body['status'] : null,
                'body' => $body,
                'error' => null,
                'retryable' => false,
            ];
        }

        $detail = $body['detail'] ?? $body['error'] ?? $body['message'] ?? mb_substr(trim($response->body()), 0, 300);

        return [
            'ok' => false,
            'http' => $response->status(),
            'status' => isset($body['status']) ? (string) $body['status'] : null,
            'body' => $body,
            'error' => 'HTTP ' . $response->status() . ($detail !== '' ? ': ' . (is_string($detail) ? $detail : json_encode($detail)) : ''),
            'retryable' => $response->serverError(),
        ];
    }

    private function http(): PendingRequest
    {
        return Http::withToken((string) config('knowledge.key'))
            ->acceptJson()
            ->timeout(max(1, (int) config('knowledge.timeout', 60)))
            ->connectTimeout(max(1, (int) config('knowledge.connect_timeout', 10)))
            ->withOptions(['verify' => config('knowledge.verify_tls', true)]);
    }

    private function url(string $path): string
    {
        return config('knowledge.base_url') . $path;
    }

    private function fakeOk(array $body): array
    {
        return ['ok' => true, 'http' => 200, 'status' => $body['status'] ?? null, 'body' => $body, 'error' => null, 'retryable' => false];
    }

    private function failure(?int $http, string $error, bool $retryable): array
    {
        return ['ok' => false, 'http' => $http, 'status' => null, 'body' => [], 'error' => $error, 'retryable' => $retryable];
    }
}
