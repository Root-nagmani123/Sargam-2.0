<?php

namespace App\Services\COE;

use App\Models\QuestionPaper;
use App\Models\QuestionPaperLog;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * COE › Question Paper Management Logs: every look at and change to a question
 * paper, by whom (and on which screen), when and from which IP address.
 *
 * The controllers record after the action succeeds. A log that cannot be
 * written never undoes or blocks the action - it is reported to the app log.
 */
class QuestionPaperLogger
{
    /** The screen the action was taken on. */
    public const ROLE_FACULTY = 'Faculty';

    public const ROLE_RAJBHASHA = 'Rajbhasha';

    public const ROLE_COE = 'COE';

    /** What was acted on: the faculty's paper, or Rajbhasha's translation of it. */
    public const MODULE_PAPER = 'Exam Paper';

    public const MODULE_TRANSLATION = 'Translation';

    public const VIEWED = 'Viewed';

    public const OPENED = 'Opened in Editor';

    public const CREATED = 'Created';

    public const UPDATED = 'Updated';

    public const DELETED = 'Deleted';

    public const SENT = 'Sent for Approval';

    public const FROZEN = 'Frozen';

    public const UNFROZEN = 'Unfrozen';

    public const PRINTED = 'Printed';

    /** Action → badge colour key (coe-log-badge--*), in the Status filter's order. */
    public const ACTIONS = [
        self::VIEWED => 'blue',
        self::OPENED => 'blue',
        self::CREATED => 'green',
        self::UPDATED => 'amber',
        self::SENT => 'green',
        self::FROZEN => 'teal',
        self::UNFROZEN => 'red',
        self::PRINTED => 'amber',
        self::DELETED => 'red',
    ];

    public const ROLES = [
        self::ROLE_FACULTY => 'blue',
        self::ROLE_RAJBHASHA => 'purple',
        self::ROLE_COE => 'red',
    ];

    public function record(string $role, string $module, string $action, QuestionPaper|int|null $paper, ?string $detail = null): void
    {
        try {
            $user = auth()->user();
            $request = request();

            QuestionPaperLog::create([
                'question_paper_id' => $paper instanceof QuestionPaper ? $paper->id : $paper,
                'user_id' => $user?->getAuthIdentifier(),
                'user_name' => $user
                    ? (trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: ($user->name ?? 'User'))
                    : 'System',
                'role' => $role,
                'module' => $module,
                'action' => $action,
                'detail' => $detail,
                'ip_address' => $request?->ip(),
                'user_agent' => mb_substr((string) $request?->userAgent(), 0, 255) ?: null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('COE question paper log not written', [
                'action' => $action,
                'paper' => $paper instanceof QuestionPaper ? $paper->id : $paper,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * The log, newest first, shaped for the Logs screen. $module narrows it
     * (the Translated tab).
     *
     * @return Collection<int, array{at:Carbon, user_name:string, role:string, module:string, action:string,
     *     detail:?string, paper_code:string, paper_name:string, drive:string, batch:string, faculty:string,
     *     ip_address:string, location:string}>
     */
    public function entries(?string $module = null): Collection
    {
        return QuestionPaperLog::with(['paper.drive', 'paper.faculty'])
            ->when($module, fn ($q) => $q->where('module', $module))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (QuestionPaperLog $log) => [
                'at' => $log->created_at,
                'user_name' => $log->user_name,
                'role' => $log->role,
                'module' => $log->module,
                'action' => $log->action,
                'detail' => $log->detail,
                'paper_code' => $log->paper->paper_code ?? '-',
                'paper_name' => $log->paper->paper_name ?? '-',
                'drive' => $log->paper->drive->drive_name ?? '-',
                'batch' => (string) ($log->paper->drive->academic_session ?? '-'),
                'faculty' => trim($log->paper->faculty->full_name ?? '-'),
                'ip_address' => $log->ip_address ?? '-',
                'location' => self::location($log->ip_address),
            ]);
    }

    /**
     * Where the request came from, as far as the IP alone tells: the
     * institute's own network (a private / local address) or outside it. A
     * city needs a geo-IP lookup, which would send the address to a third party.
     */
    public static function location(?string $ip): string
    {
        if (! $ip || ! filter_var($ip, FILTER_VALIDATE_IP)) {
            return '-';
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
            ? 'Outside Network'
            : 'Internal Network';
    }
}
