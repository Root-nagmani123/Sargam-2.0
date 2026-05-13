<?php

namespace App\Services;

use App\Models\NoticeNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CampusNoticeNotificationDispatcher
{
    /**
     * Push in-app notifications for a newly created campus notice (best-effort).
     */
    public function dispatch(NoticeNotification $notice): void
    {
        if (!Schema::hasTable('notifications')) {
            return;
        }

        $receiverIds = $this->receiverUserIds($notice);
        if ($receiverIds === []) {
            return;
        }

        $title = 'New notice: '.$notice->notice_title;
        $plain = trim(preg_replace('/\s+/', ' ', strip_tags((string) $notice->description)));
        $message = Str::limit($plain, 240);

        $senderId = Auth::user() ? (int) Auth::user()->user_id : null;

        try {
            /** @var NotificationService $svc */
            $svc = app(NotificationService::class);
            foreach (array_chunk($receiverIds, 400) as $chunk) {
                $svc->createMultiple(
                    $chunk,
                    'campus_notice',
                    'Notice',
                    (int) $notice->pk,
                    $title,
                    $message,
                    $senderId
                );
            }
        } catch (\Throwable $e) {
            Log::error('CampusNoticeNotificationDispatcher: '.$e->getMessage(), ['notice_pk' => $notice->pk]);
        }
    }

    /**
     * @return list<int>
     */
    private function receiverUserIds(NoticeNotification $notice): array
    {
        $aud = (string) $notice->target_audience;

        if ($aud === 'All') {
            return DB::table('user_credentials')
                ->whereNotNull('user_id')
                ->distinct()
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        if ($aud === 'Staff/Faculty') {
            $roleLabels = ['Internal Faculty', 'Guest Faculty', 'Training', 'Staff'];

            return DB::table('employee_role_mapping as erm')
                ->join('user_role_master as urm', 'urm.pk', '=', 'erm.user_role_master_pk')
                ->join('user_credentials as uc', 'uc.pk', '=', 'erm.user_credentials_pk')
                ->where(function ($q) use ($roleLabels) {
                    $q->whereIn('urm.user_role_display_name', $roleLabels)
                        ->orWhereIn('urm.user_role_name', $roleLabels);
                })
                ->whereNotNull('uc.user_id')
                ->distinct()
                ->pluck('uc.user_id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        if ($aud === 'Office trainee' && $notice->course_master_pk) {
            return DB::table('student_master_course__map as smcm')
                ->where('smcm.course_master_pk', $notice->course_master_pk)
                ->whereNotNull('smcm.student_master_pk')
                ->distinct()
                ->pluck('smcm.student_master_pk')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        return [];
    }
}
