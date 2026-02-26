<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\IssueLogManagement;
use App\Models\IssueCategoryEmployeeMap;
use App\Services\NotificationService;
use Carbon\Carbon;

class SendIssueEscalationNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'issues:send-escalation-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send escalation notifications for pending issues based on escalation matrix';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $now = Carbon::now();

        // Only issues that are not completed (still open/pending)
        $issues = IssueLogManagement::where('issue_status', '!=', IssueLogManagement::STATUS_COMPLETED)
            ->get();

        foreach ($issues as $issue) {
            $this->processIssueEscalations($issue, $now);
        }

        return Command::SUCCESS;
    }

    /**
     * Process escalation notifications for a single issue.
     */
    protected function processIssueEscalations(IssueLogManagement $issue, Carbon $now): void
    {
        $createdAt = $issue->created_date instanceof Carbon
            ? $issue->created_date
            : Carbon::parse($issue->created_date);

        // Load escalation matrix for this issue's category
        $levels = IssueCategoryEmployeeMap::where('issue_category_master_pk', $issue->issue_category_master_pk)
            ->get()
            ->keyBy('priority');

        if ($levels->isEmpty()) {
            return;
        }

        // Level 2 escalation
        if (isset($levels[2])) {
            $this->maybeNotifyLevel(
                levelPriority: 2,
                levelConfig: $levels[2],
                issue: $issue,
                createdAt: $createdAt,
                now: $now
            );
        }

        // Level 3 escalation
        if (isset($levels[3])) {
            $this->maybeNotifyLevel(
                levelPriority: 3,
                levelConfig: $levels[3],
                issue: $issue,
                createdAt: $createdAt,
                now: $now
            );
        }
    }

    /**
     * Check if a specific escalation level should be notified and send notification if required.
     */
    protected function maybeNotifyLevel(
        int $levelPriority,
        IssueCategoryEmployeeMap $levelConfig,
        IssueLogManagement $issue,
        Carbon $createdAt,
        Carbon $now
    ): void {
        $daysNotify = (int) ($levelConfig->days_notify ?? 0);
        if ($daysNotify <= 0) {
            return;
        }

        $thresholdDate = $createdAt->copy()->addDays($daysNotify);
        if ($now->lt($thresholdDate)) {
            return;
        }

        $receiverUserId = (int) $levelConfig->employee_master_pk;
        if ($receiverUserId <= 0) {
            return;
        }

        // Use existing NotificationService (same pattern as other modules)
        $title = 'Issue Escalation - Level ' . $levelPriority;
        $message = sprintf(
            'Complaint #%d is still pending after %d day(s) and has been escalated to you.',
            $issue->pk,
            $daysNotify
        );

        try {
            app(NotificationService::class)->create(
                $receiverUserId,
                'issue',
                'IssueEscalation',
                (int) $issue->pk,
                $title,
                $message
            );
        } catch (\Throwable $e) {
            // Do not break the loop if notification fails; just log and continue
            report($e);
            return;
        }
    }
}

