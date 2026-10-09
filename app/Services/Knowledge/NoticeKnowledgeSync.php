<?php

namespace App\Services\Knowledge;

use App\Jobs\SyncNoticeToKnowledge;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Keeps ज्ञानकोश in step with notices_notification.
 *
 * A notice is "published" — and belongs in ज्ञानकोश — while it is active, its
 * display date has come, it has not expired, it carries a document, and it is
 * not held back by config/knowledge.php (personal or course-scoped notices).
 * The same rule decides the dashboard feed (notice_feed_base_query()), so the
 * knowledge base never quotes a notice the portal itself no longer shows.
 *
 * sync() moves one notice towards that state: push a new edition, withdraw it,
 * or do nothing. It is idempotent — the version sent is a hash of exactly what
 * is sent, so a retry or a re-run carries the same external_version and the API
 * answers already_present.
 */
class NoticeKnowledgeSync
{
    public const TABLE = 'notices_notification';

    public function __construct(private KnowledgeIngestClient $client)
    {
    }

    /**
     * Fire-and-forget hook for controllers: sync this notice after the response
     * is sent, so a slow or unreachable knowledge base never delays a save.
     */
    public static function queue(int $noticePk): void
    {
        if (! config('knowledge.enabled')) {
            return;
        }

        try {
            SyncNoticeToKnowledge::dispatchAfterResponse($noticePk);
        } catch (Throwable $e) {
            Log::error('Knowledge ingest: could not schedule notice sync', ['notice' => $noticePk, 'error' => $e->getMessage()]);
        }
    }

    public function externalId(int $noticePk): string
    {
        return config('knowledge.notice_id_prefix', 'SARGAM-NOTICE-') . $noticePk;
    }

    /**
     * @return string what happened: pushed, unchanged, withdrawn, skipped, failed, disabled
     */
    public function sync(int $noticePk): string
    {
        if (! $this->client->enabled()) {
            return 'disabled';
        }

        $notice = $this->noticeQuery()->where('n.pk', $noticePk)->first();

        // Deleted on Sargam: nothing left to record against, so withdraw
        // unconditionally — "not_present" is a success on their side.
        if ($notice === null) {
            $result = $this->client->withdraw($this->externalId($noticePk), 'deleted on Sargam');
            $this->logResult('withdraw', $noticePk, $result);

            return $result['ok'] ? 'withdrawn' : 'failed';
        }

        $holdBack = $this->holdBackReason($notice);

        if ($holdBack !== null) {
            return $this->ensureWithdrawn($notice, $holdBack);
        }

        return $this->push($notice);
    }

    /**
     * Ids of every notice currently published — the list /ingest/reconcile
     * compares against.
     *
     * @return array<int, string>
     */
    public function publishedExternalIds(): array
    {
        return $this->candidateQuery()
            ->get()
            ->filter(fn ($notice) => $this->holdBackReason($notice) === null)
            ->map(fn ($notice) => $this->externalId((int) $notice->pk))
            ->values()
            ->all();
    }

    /**
     * Notices that need a call: published ones (pushed only if their version
     * moved) plus any ज्ञानकोश may still hold that are no longer published.
     *
     * @return array<int, int>
     */
    public function pksNeedingSync(): array
    {
        $published = $this->candidateQuery()->pluck('n.pk');

        $heldThere = DB::table(self::TABLE)
            ->where(function ($q) {
                $q->whereNotNull('knowledge_version')
                    ->orWhere('knowledge_sync_status', 'failed');
            })
            ->pluck('pk');

        return $published->merge($heldThere)->map(fn ($pk) => (int) $pk)->unique()->values()->all();
    }

    /** Null when the notice should be in ज्ञानकोश, else why it should not. */
    public function holdBackReason(object $notice): ?string
    {
        $today = Carbon::today()->toDateString();

        if ((int) $notice->active_inactive !== 1) {
            return 'deactivated on Sargam';
        }
        if ($notice->display_date !== null && Carbon::parse($notice->display_date)->toDateString() > $today) {
            return 'not yet displayed on Sargam';
        }
        if ($notice->expiry_date !== null && Carbon::parse($notice->expiry_date)->toDateString() < $today) {
            return 'expired on Sargam';
        }
        if (in_array((string) $notice->notice_type, config('knowledge.notices.exclude_types', []), true)) {
            return 'notice type "' . $notice->notice_type . '" is not shared Academy-wide';
        }
        if (! empty($notice->course_master_pk) && ! config('knowledge.notices.include_course_notices')) {
            return 'course-specific notice is not shared Academy-wide';
        }
        if ($this->documentPath($notice) === null) {
            return 'no document file';
        }

        return null;
    }

    private function push(object $notice): string
    {
        $path = $this->documentPath($notice);
        $fields = $this->fields($notice);
        $fields['external_version'] = $this->version($fields, $path);

        // Already live at exactly this edition — nothing to send.
        if ($notice->knowledge_version === $fields['external_version']
            && $notice->knowledge_sync_status === 'live') {
            return 'unchanged';
        }

        $result = $this->client->ingest($fields, $path, $this->uploadFilename($notice, $path));
        $this->logResult('ingest', (int) $notice->pk, $result);

        if ($result['ok']) {
            $this->record((int) $notice->pk, [
                'knowledge_sync_status' => 'live',
                'knowledge_version' => $fields['external_version'],
                'knowledge_doc_id' => $result['body']['document_id'] ?? $notice->knowledge_doc_id,
                'knowledge_synced_at' => now(),
                'knowledge_attempts' => 0,
                'knowledge_error' => null,
            ]);

            return 'pushed';
        }

        // knowledge_version is left alone: if an earlier edition is live there,
        // it still is.
        $this->recordFailure($notice, $result);

        return 'failed';
    }

    private function ensureWithdrawn(object $notice, string $reason): string
    {
        // Withdraw when they may hold it: a known live edition, or a failed
        // attempt (a timed-out push may have been stored).
        $mayBeHeld = $notice->knowledge_version !== null || $notice->knowledge_sync_status === 'failed';

        if (! $mayBeHeld) {
            if ($notice->knowledge_sync_status !== 'skipped' || $notice->knowledge_error !== $reason) {
                $this->record((int) $notice->pk, [
                    'knowledge_sync_status' => 'skipped',
                    'knowledge_error' => $reason,
                    'knowledge_attempts' => 0,
                ]);
            }

            return 'skipped';
        }

        $result = $this->client->withdraw($this->externalId((int) $notice->pk), $reason);
        $this->logResult('withdraw', (int) $notice->pk, $result);

        if ($result['ok']) {
            $this->record((int) $notice->pk, [
                'knowledge_sync_status' => 'withdrawn',
                'knowledge_version' => null,
                'knowledge_synced_at' => now(),
                'knowledge_attempts' => 0,
                'knowledge_error' => $reason,
            ]);

            return 'withdrawn';
        }

        $this->recordFailure($notice, $result);

        return 'failed';
    }

    /** @return array<string, string> */
    private function fields(object $notice): array
    {
        $department = trim((string) $notice->author_department);

        $fields = [
            'external_id' => $this->externalId((int) $notice->pk),
            'title' => trim((string) $notice->notice_title),
            'kind' => config('knowledge.notices.kind_map.' . $notice->notice_type)
                ?? config('knowledge.notices.default_kind', 'other'),
            'issued_on' => $notice->display_date ? Carbon::parse($notice->display_date)->toDateString() : null,
            'issued_by' => $department,
            'section' => $department,
        ];

        return array_filter($fields, fn ($v) => $v !== null && $v !== '');
    }

    /**
     * Deterministic edition id: changes when the file or any metadata we send
     * changes, and only then.
     */
    private function version(array $fields, string $path): string
    {
        ksort($fields);

        return substr(hash('sha256', json_encode($fields, JSON_UNESCAPED_UNICODE) . '|' . hash_file('sha256', $path)), 0, 16);
    }

    private function documentPath(object $notice): ?string
    {
        $document = trim((string) ($notice->document ?? ''));
        if ($document === '') {
            return null;
        }

        // Older rows may carry the "public/" prefix of the store('public/...') form.
        $relative = preg_replace('#^/?(storage/|public/)#', '', $document);
        $disk = Storage::disk('public');

        return $disk->exists($relative) ? $disk->path($relative) : null;
    }

    private function uploadFilename(object $notice, string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION)) ?: 'pdf';
        $slug = Str::limit(Str::slug((string) $notice->notice_title), 80, '') ?: 'notice';

        return $slug . '-' . $notice->pk . '.' . $extension;
    }

    private function noticeQuery()
    {
        return DB::table(self::TABLE . ' as n')
            ->leftJoin('user_credentials as notice_author', 'notice_author.pk', '=', 'n.created_by')
            ->leftJoin('employee_master as notice_author_emp', 'notice_author_emp.pk', '=', 'notice_author.user_id')
            ->leftJoin('department_master as notice_author_dept', 'notice_author_dept.pk', '=', 'notice_author_emp.department_master_pk')
            ->select(
                'n.pk', 'n.notice_title', 'n.notice_type', 'n.document', 'n.display_date', 'n.expiry_date',
                'n.course_master_pk', 'n.active_inactive',
                'n.knowledge_sync_status', 'n.knowledge_version', 'n.knowledge_doc_id', 'n.knowledge_error',
                'notice_author_dept.department_name as author_department'
            );
    }

    /** Coarse SQL pre-filter; holdBackReason() is the authority. */
    private function candidateQuery()
    {
        $today = Carbon::today()->toDateString();

        return $this->noticeQuery()
            ->where('n.active_inactive', 1)
            ->where('n.display_date', '<=', $today)
            ->where('n.expiry_date', '>=', $today)
            ->whereNotNull('n.document')
            ->where('n.document', '<>', '');
    }

    private function record(int $pk, array $values): void
    {
        DB::table(self::TABLE)->where('pk', $pk)->update($values + ['knowledge_last_attempt_at' => now()]);
    }

    private function recordFailure(object $notice, array $result): void
    {
        DB::table(self::TABLE)->where('pk', $notice->pk)->update([
            'knowledge_sync_status' => 'failed',
            'knowledge_error' => mb_substr((string) $result['error'], 0, 2000),
            'knowledge_attempts' => DB::raw('LEAST(knowledge_attempts + 1, 65535)'),
            'knowledge_last_attempt_at' => now(),
        ]);
    }

    private function logResult(string $action, int $pk, array $result): void
    {
        $context = [
            'notice' => $pk,
            'external_id' => $this->externalId($pk),
            'http' => $result['http'],
            'status' => $result['status'],
        ];

        if ($result['ok']) {
            Log::info("Knowledge ingest: {$action} ok", $context);
        } elseif ($result['http'] === 401) {
            Log::critical("Knowledge ingest: {$action} refused — key missing, wrong or revoked", $context);
        } else {
            Log::error("Knowledge ingest: {$action} failed", $context + ['error' => $result['error'], 'retryable' => $result['retryable']]);
        }
    }
}
