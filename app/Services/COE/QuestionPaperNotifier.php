<?php

namespace App\Services\COE;

use App\Models\FacultyMaster;
use App\Models\QuestionPaper;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Dashboard (bell) notifications as a question paper moves between the
 * faculty, Rajbhasha and COE (the BRD's notification matrix, N-027 to N-038).
 * Clicking one opens the receiver's own Question Paper list
 * (config/notifications.php coe_question_paper).
 *
 * Who gets them: the paper's faculty through faculty_master.employee_master_pk
 * (= user_credentials.user_id - without a linked login they cannot be told),
 * Rajbhasha as whoever holds RajbhashaPaperService::PERMISSION, COE as the COE
 * role. The one acting is never told of their own action.
 *
 * The controllers call these after the action succeeds. A notification that
 * cannot be sent never undoes or blocks the action - it is reported to the app log.
 */
class QuestionPaperNotifier
{
    public const TYPE = 'coe_question_paper';

    /** Module → the list the bell opens (config/notifications.php). */
    public const MODULE_FACULTY = 'FacultyQuestionPaper';

    public const MODULE_RAJBHASHA = 'RajbhashaQuestionPaper';

    public const MODULE_COE = 'CoeQuestionPaper';

    public const COE_ROLE = 'COE';

    public function __construct(private NotificationService $notifications)
    {
    }

    /** N-028 / N-027: the faculty uploaded V1, or an edited version, for translation → Rajbhasha. */
    public function sentForTranslation(QuestionPaper $paper): void
    {
        if ($paper->version > 1) {
            $title = 'Question Paper Updated';
            $message = 'The question paper for '.$this->subject($paper).' has been updated (V'.$paper->version.') and is ready for Hindi translation.';
        } else {
            $title = 'Question Paper for Translation';
            $message = 'A question paper for '.$this->subject($paper).' has been submitted for Hindi translation.';
        }
        $this->send($paper, self::MODULE_RAJBHASHA, $this->rajbhashaUserIds(), $title, $message);
    }

    /** N-029 / N-030 (one moment: sent for approval is Pending Verification) → the faculty. */
    public function translationReady(QuestionPaper $paper): void
    {
        $this->send($paper, self::MODULE_FACULTY, $this->facultyUserIds($paper), 'Hindi Translation Ready',
            'The Hindi translation for '.$this->subject($paper).' is ready. Review it and freeze it when final.');
    }

    /** N-032: the faculty froze and published it → COE (the faculty already sees the confirmation). */
    public function published(QuestionPaper $paper): void
    {
        $this->send($paper, self::MODULE_COE, $this->coeUserIds(), 'Question Paper Published',
            'The question paper for '.$this->subject($paper).' has been frozen and published by the faculty.');
    }

    /** N-037: COE unfroze a published paper → the faculty and Rajbhasha. */
    public function reopened(QuestionPaper $paper): void
    {
        $title = 'Question Paper Reopened';
        $message = 'The published question paper for '.$this->subject($paper).' has been reopened and is pending verification.';
        $this->send($paper, self::MODULE_FACULTY, $this->facultyUserIds($paper), $title, $message);
        $this->send($paper, self::MODULE_RAJBHASHA, $this->rajbhashaUserIds(), $title, $message);
    }

    /** N-038: COE deleted the paper (it stays soft-deleted) → the faculty and Rajbhasha. */
    public function deleted(QuestionPaper $paper): void
    {
        $title = 'Question Paper Deleted';
        $message = 'The question paper for '.$this->subject($paper).' has been deleted. Contact COE if this was unexpected.';
        $this->send($paper, self::MODULE_FACULTY, $this->facultyUserIds($paper), $title, $message);
        $this->send($paper, self::MODULE_RAJBHASHA, $this->rajbhashaUserIds(), $title, $message);
    }

    /** @param  callable(): int[]  $receivers  resolved inside the guard, so a lookup failure is only logged */
    private function send(QuestionPaper $paper, string $module, callable $receivers, string $title, string $message): void
    {
        try {
            $userIds = array_values(array_diff(array_unique($receivers()), [(int) auth()->user()?->user_id]));
            if ($userIds !== []) {
                $this->notifications->createMultiple($userIds, self::TYPE, $module, $paper->id, $title, $message);
            }
        } catch (\Throwable $e) {
            Log::warning('COE question paper notification not sent', [
                'paper' => $paper->id,
                'module' => $module,
                'title' => $title,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** "Counselling (QP-3-015)" */
    private function subject(QuestionPaper $paper): string
    {
        return trim($paper->paper_name.' ('.$paper->paper_code.')');
    }

    private function facultyUserIds(QuestionPaper $paper): callable
    {
        return fn () => FacultyMaster::where('pk', $paper->faculty_master_pk)
            ->whereIn('employee_master_pk', DB::table('user_credentials')->select('user_id'))
            ->pluck('employee_master_pk')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function rajbhashaUserIds(): callable
    {
        return fn () => User::permission(RajbhashaPaperService::PERMISSION)->pluck('user_id')->map(fn ($id) => (int) $id)->all();
    }

    private function coeUserIds(): callable
    {
        return fn () => User::role(self::COE_ROLE)->pluck('user_id')->map(fn ($id) => (int) $id)->all();
    }
}
