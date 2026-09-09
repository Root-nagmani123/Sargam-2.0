<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate the Memo / Notice module's write actions to the roles allowed to run it.
 *
 * The controller already had this rule — CourseAttendanceNoticeMapController::
 * userCanManageMemoNotice() — but applied it method by method, and four of nine write
 * methods were missing it: creating a Show Cause Memo, closing a case with a marks
 * deduction, issuing a notice directly, and deleting messages from a disciplinary
 * conversation all ran for any authenticated user.
 *
 * Attaching it to the routes instead of repeating it in each method is the point: a
 * method added tomorrow inherits the check instead of having to remember it. The
 * per-method calls that already exist are left in place — they are harmless once this
 * runs first, and they keep the controller readable on its own.
 *
 * Deliberately NOT applied to the participant-facing routes in the same group
 * (/user, /conversation_student, memo_notice_conversation_student): those exist so an
 * Officer Trainee can read and reply to their own notice, and gating them behind faculty
 * roles would break the reply path this module is built around.
 */
class EnsureMemoNoticeManager
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->canManage()) {
            $message = 'You are not authorised to manage memos or notices.';

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $message], 403)
                : abort(403, $message);
        }

        return $next($request);
    }

    /**
     * Mirrors CourseAttendanceNoticeMapController::userCanManageMemoNotice() exactly.
     * If that list changes, change it here too — or better, move both to one helper.
     */
    private function canManage(): bool
    {
        return hasRole('Internal Faculty')
            || hasRole('Guest Faculty')
            || hasRole('Super Admin')
            || hasRole('Training Induction Admin')
            || hasRole('Training-Induction');
    }
}
