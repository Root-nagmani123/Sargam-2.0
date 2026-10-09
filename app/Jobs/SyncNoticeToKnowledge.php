<?php

namespace App\Jobs;

use App\Services\Knowledge\NoticeKnowledgeSync;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pushes / withdraws one notice in ज्ञानकोश. Dispatched after the response by
 * NoticeKnowledgeSync::queue(). One attempt only: a failure is recorded on the
 * notice row and retried by the scheduled `knowledge:sync-notices`, which is
 * also what retries with backoff over a day as the API contract asks.
 */
class SyncNoticeToKnowledge implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public int $noticePk)
    {
    }

    public function handle(NoticeKnowledgeSync $sync): void
    {
        try {
            $sync->sync($this->noticePk);
        } catch (Throwable $e) {
            Log::error('Knowledge ingest: notice sync crashed', [
                'notice' => $this->noticePk,
                'error' => get_class($e) . ': ' . $e->getMessage(),
            ]);
        }
    }
}
