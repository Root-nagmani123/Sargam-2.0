<?php

namespace App\Console\Commands;

use App\Services\FC\FcAdminSmsBulkService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs one "Bulk SMS/Email" send in a detached background process, so the
 * admin's browser request can return immediately instead of waiting on
 * live SMS-gateway + SMTP calls for every recipient (see
 * FcAdminSmsController@send, which spawns this instead of calling
 * FcAdminSmsBulkService::send() inline).
 *
 * php artisan fc:admin-sms-send --template=b1 --form-id=30
 * php artisan fc:admin-sms-send --template=b2 --form-id=30 --pks=101,102,103
 * php artisan fc:admin-sms-send --template=b3 --form-id=30
 */
class FcAdminSmsSendCommand extends Command
{
    protected $signature = 'fc:admin-sms-send
        {--template= : b1, b2, or b3}
        {--form-id= : fc_forms.id}
        {--pks= : Comma-separated registration pks, or omit for all matching}
        {--lock-key= : Cache lock key to release when this run finishes (success or failure)}';

    protected $description = 'Background worker for one FC admin Bulk SMS/Email send (spawned per request, not a long-running queue).';

    public function handle(FcAdminSmsBulkService $bulk): int
    {
        $template = strtolower((string) $this->option('template'));
        $formId = (int) $this->option('form-id');
        $pksOption = $this->option('pks');
        $lockKey = (string) $this->option('lock-key');

        $pks = null;
        if (is_string($pksOption) && trim($pksOption) !== '') {
            $pks = array_values(array_unique(array_filter(array_map(
                'intval',
                explode(',', $pksOption)
            ), fn ($v) => $v > 0)));
        }

        if (! in_array($template, FcAdminSmsBulkService::VALID_TEMPLATES, true) || $formId <= 0) {
            Log::error('fc:admin-sms-send — invalid arguments.', [
                'template' => $template,
                'form_id' => $formId,
            ]);
            $this->releaseLock($lockKey);

            return self::FAILURE;
        }

        try {
            $result = $bulk->send($template, $pks, $formId);

            Log::info('FC admin bulk send (background process) finished.', [
                'template' => $template,
                'form_id' => $formId,
                'selected_only' => $pks !== null,
                'selected_count' => $pks !== null ? count($pks) : null,
                'result' => $result,
            ]);

            return self::SUCCESS;
        } catch (Throwable $e) {
            // Individual recipient failures are already caught inside
            // FcAdminSmsBulkService — reaching here means something broke before/
            // between recipients (DB drop, form lookup, etc). Logged explicitly so
            // this shows up as a clear failure instead of a job that just stops
            // with no "finished" line.
            Log::error('FC admin bulk send (background process) FAILED: '.$e->getMessage(), [
                'template' => $template,
                'form_id' => $formId,
                'selected_only' => $pks !== null,
                'selected_count' => $pks !== null ? count($pks) : null,
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        } finally {
            $this->releaseLock($lockKey);
        }
    }

    private function releaseLock(string $lockKey): void
    {
        if ($lockKey === '') {
            return;
        }

        // Same TTL as when acquired in FcAdminSmsController@send — forceRelease()
        // needs a Lock instance built with matching parameters to target the same key.
        Cache::lock($lockKey)->forceRelease();
    }
}
