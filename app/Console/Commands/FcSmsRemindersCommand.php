<?php

namespace App\Console\Commands;

use App\Models\PathPage;
use App\Services\FC\FcNotifyService;
use App\Services\FC\FcRosterApplicationGuardService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * B1 / B2 / D6 SMS reminders (memo/notice skipped). Uses SMS_DRIVER=log|gupshup.
 *
 * Examples:
 *  php artisan fc:sms-reminders --type=b1 --step="Bank Details"
 *  php artisan fc:sms-reminders --type=b2
 *  php artisan fc:sms-reminders --type=d6 --programme="Foundation Course 2026-Batch1" --last-date=25-Jul-2026
 *  php artisan fc:sms-reminders --type=b1 --step="Bank Details" --pk=123
 */
class FcSmsRemindersCommand extends Command
{
    protected $signature = 'fc:sms-reminders
        {--type=b2 : b1 (step incomplete), b2 (pending deadline), d6 (feedback request)}
        {--step= : Step name for B1 (e.g. "Bank Details")}
        {--pk= : Optional single fc_registration_master.pk}
        {--programme= : Programme / course label}
        {--last-date= : Deadline text (e.g. 20-Jul-2026)}
        {--feedback-link= : Feedback URL for D6}
        {--limit=200 : Max recipients}
        {--dry-run : List recipients without sending}';

    protected $description = 'Send FC SMS reminders for B1 / B2 / D6 (not memo/notice)';

    public function handle(FcNotifyService $notify): int
    {
        $type = strtolower((string) $this->option('type'));
        if (!in_array($type, ['b1', 'b2', 'd6'], true)) {
            $this->error('Invalid --type. Use b1, b2, or d6.');
            return self::FAILURE;
        }

        if ($type === 'b1' && trim((string) $this->option('step')) === '') {
            $this->error('B1 requires --step="Section Name".');
            return self::FAILURE;
        }

        $rows = $this->recipients($type);
        if ($rows->isEmpty()) {
            $this->warn('No recipients found.');
            return self::SUCCESS;
        }

        $programme = trim((string) $this->option('programme'));
        if ($programme === '') {
            $programme = (string) config('gupshup.default_programme_name', 'Foundation Course');
        }

        $lastDate = trim((string) $this->option('last-date'));
        if ($lastDate === '' && $type === 'b2') {
            $lastDate = $this->registrationDeadlineText();
        }
        if ($lastDate === '') {
            $lastDate = now()->addDays(7)->format('d-M-Y');
        }

        $step = trim((string) $this->option('step'));
        $feedbackLink = trim((string) $this->option('feedback-link')) ?: (string) config('gupshup.portal_url');
        $dryRun = (bool) $this->option('dry-run');
        $sent = 0;

        foreach ($rows as $row) {
            $mobile = trim((string) ($row->contact_no ?? ''));
            $name = trim((string) ($row->display_name ?? ''));
            $pk = isset($row->pk) ? (int) $row->pk : null;

            if ($mobile === '') {
                continue;
            }

            $this->line(($dryRun ? '[dry-run] ' : '')."{$mobile} — {$name}");

            if ($dryRun) {
                $sent++;
                continue;
            }

            match ($type) {
                'b1' => $notify->formStepIncomplete($mobile, $name, $step, $pk),
                'b2' => $notify->registrationPending($mobile, $name, $programme, $lastDate, $pk),
                'd6' => $notify->feedbackRequest($mobile, $name, $programme, $lastDate, $feedbackLink, $pk),
            };
            $sent++;
        }

        $this->info(($dryRun ? 'Dry-run listed ' : 'Processed ').$sent.' recipient(s). Check laravel.log when SMS_DRIVER=log.');

        return self::SUCCESS;
    }

    protected function recipients(string $type)
    {
        $limit = max(1, (int) $this->option('limit'));
        $pk = $this->option('pk');

        if ($type === 'd6') {
            return $this->feedbackRecipients($limit, $pk);
        }

        if (!Schema::hasTable('fc_registration_master')) {
            return collect();
        }

        $query = DB::table('fc_registration_master')
            ->select('pk', 'display_name', 'contact_no')
            ->whereNotNull('contact_no')
            ->where('contact_no', '!=', '');

        if ($pk !== null && $pk !== '') {
            $query->where('pk', (int) $pk);
        } else {
            // Incomplete registration cohort (same idea as status incomplete tab).
            if (Schema::hasColumn('fc_registration_master', 'is_registered')) {
                $query->where(function ($q) {
                    $q->where('is_registered', 0)->orWhereNull('is_registered');
                });
            }
            if (Schema::hasColumn('fc_registration_master', 'application_type')) {
                $query->where(function ($q) {
                    $q->whereNull('application_type')
                        ->orWhere('application_type', '!=', FcRosterApplicationGuardService::APPLICATION_EXEMPTION);
                });
            }
        }

        return $query->orderBy('pk')->limit($limit)->get();
    }

    protected function feedbackRecipients(int $limit, $pk)
    {
        // Prefer roster mobiles for FC programme reminders; refine later with pending-feedback joins.
        if (!Schema::hasTable('fc_registration_master')) {
            return collect();
        }

        $query = DB::table('fc_registration_master')
            ->select('pk', 'display_name', 'contact_no')
            ->whereNotNull('contact_no')
            ->where('contact_no', '!=', '');

        if ($pk !== null && $pk !== '') {
            $query->where('pk', (int) $pk);
        } elseif (Schema::hasColumn('fc_registration_master', 'is_registered')) {
            $query->where('is_registered', 1);
        }

        return $query->orderBy('pk')->limit($limit)->get();
    }

    protected function registrationDeadlineText(): string
    {
        try {
            $path = PathPage::query()->first();
            $end = $path->registration_end_date ?? null;
            if ($end) {
                return Carbon::parse($end)->format('d-M-Y');
            }
        } catch (\Throwable $e) {
            // fall through
        }

        return now()->addDays(7)->format('d-M-Y');
    }
}
