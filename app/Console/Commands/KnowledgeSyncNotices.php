<?php

namespace App\Console\Commands;

use App\Services\Knowledge\KnowledgeIngestClient;
use App\Services\Knowledge\NoticeKnowledgeSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Catch-up and safety net for the ज्ञानकोश notice feed.
 *
 *   knowledge:sync-notices                 push/withdraw whatever is out of step
 *   knowledge:sync-notices --reconcile     …then send the daily published-id list
 *   knowledge:sync-notices --reconcile --dry-run   ask what reconcile WOULD withdraw
 *   knowledge:sync-notices --notice=39     one notice only
 *   knowledge:sync-notices --status        list notices whose last attempt failed
 *
 * Retries are this command: anything that failed is picked up on the next run.
 */
class KnowledgeSyncNotices extends Command
{
    protected $signature = 'knowledge:sync-notices
                            {--notice= : Sync only this notices_notification.pk}
                            {--reconcile : Also send the full published-id list (daily)}
                            {--dry-run : With --reconcile, change nothing on their side}
                            {--status : Show failed notices and exit}';

    protected $description = 'Push Sargam notices to ज्ञानकोश, withdraw unpublished ones, and reconcile';

    public function handle(NoticeKnowledgeSync $sync, KnowledgeIngestClient $client): int
    {
        if ($this->option('status')) {
            return $this->showStatus();
        }

        if (! $client->enabled()) {
            $this->warn('KNOWLEDGE_INGEST_ENABLED is off — nothing sent.');

            return self::SUCCESS;
        }

        if (! $client->isConfigured()) {
            $this->error('Knowledge ingest driver "' . $client->driver() . '" is not configured (URL / key).');

            return self::FAILURE;
        }

        $this->line('Driver: ' . $client->driver() . ($client->driver() === 'log' ? ' (nothing leaves this server)' : ''));

        $pks = $this->option('notice') !== null
            ? [(int) $this->option('notice')]
            : $sync->pksNeedingSync();

        $tally = [];
        foreach ($pks as $pk) {
            $outcome = $sync->sync($pk);
            $tally[$outcome] = ($tally[$outcome] ?? 0) + 1;
            if ($outcome !== 'unchanged' && $outcome !== 'skipped') {
                $this->line(sprintf('  notice %-6d %s', $pk, $outcome));
            }
        }

        $this->info('Notices checked: ' . count($pks) . ($tally ? ' — ' . collect($tally)->map(fn ($n, $k) => "$k $n")->implode(', ') : ''));

        $exit = ($tally['failed'] ?? 0) > 0 ? self::FAILURE : self::SUCCESS;

        if ($this->option('reconcile') && $this->option('notice') === null) {
            $exit = max($exit, $this->reconcile($sync, $client));
        }

        return $exit;
    }

    private function reconcile(NoticeKnowledgeSync $sync, KnowledgeIngestClient $client): int
    {
        $ids = $sync->publishedExternalIds();
        $dryRun = (bool) $this->option('dry-run');

        $result = $client->reconcile($ids, $dryRun);

        if ($result['ok']) {
            $this->info('Reconcile' . ($dryRun ? ' (dry run)' : '') . ': sent ' . count($ids) . ' published ids.');
            if ($result['body'] !== []) {
                $this->line(json_encode($result['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
            Log::info('Knowledge ingest: reconcile ok', ['ids' => count($ids), 'dry_run' => $dryRun, 'response' => $result['body']]);

            return self::SUCCESS;
        }

        if ($result['http'] === 409) {
            // Their shrinkage guard: our list is under half of what they hold.
            // Nothing was withdrawn. Look at our end first.
            $this->error('Reconcile refused (409): our published list is under half of what ज्ञानकोश holds. Nothing was withdrawn.');
            $this->line(json_encode($result['body'], JSON_UNESCAPED_UNICODE));
            Log::critical('Knowledge ingest: reconcile refused by shrinkage guard', ['ids' => count($ids), 'response' => $result['body']]);

            return self::FAILURE;
        }

        $this->error('Reconcile failed: ' . $result['error']);
        Log::error('Knowledge ingest: reconcile failed', ['ids' => count($ids), 'error' => $result['error'], 'http' => $result['http']]);

        return self::FAILURE;
    }

    private function showStatus(): int
    {
        $rows = DB::table(NoticeKnowledgeSync::TABLE)
            ->whereNotNull('knowledge_sync_status')
            ->select('knowledge_sync_status', DB::raw('COUNT(*) as total'))
            ->groupBy('knowledge_sync_status')
            ->pluck('total', 'knowledge_sync_status');

        $this->table(['Status', 'Notices'], $rows->map(fn ($n, $s) => [$s, $n])->values()->all());

        $failed = DB::table(NoticeKnowledgeSync::TABLE)
            ->where('knowledge_sync_status', 'failed')
            ->orderByDesc('knowledge_last_attempt_at')
            ->get(['pk', 'notice_title', 'knowledge_attempts', 'knowledge_last_attempt_at', 'knowledge_error']);

        if ($failed->isEmpty()) {
            $this->info('No failed notices.');

            return self::SUCCESS;
        }

        $this->error($failed->count() . ' notice(s) not in ज्ञानकोश as they should be:');
        $this->table(
            ['pk', 'Title', 'Attempts', 'Last attempt', 'Error'],
            $failed->map(fn ($r) => [
                $r->pk,
                mb_strimwidth((string) $r->notice_title, 0, 40, '…'),
                $r->knowledge_attempts,
                $r->knowledge_last_attempt_at,
                mb_strimwidth((string) $r->knowledge_error, 0, 80, '…'),
            ])->all()
        );

        return self::FAILURE;
    }
}
