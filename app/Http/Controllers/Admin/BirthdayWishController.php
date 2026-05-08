<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeMaster;
use App\Models\Notification;
use App\Services\Messaging\EmailService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BirthdayWishController extends Controller
{
    public function index()
    {
        $todayBirthdays = EmployeeMaster::where('status', 1)
            ->whereRaw("DATE_FORMAT(dob, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')")
            ->where('employee_master.pk', '!=', Auth::user()->user_id ?? 0)
            ->leftJoin('designation_master', 'employee_master.designation_master_pk', '=', 'designation_master.pk')
            ->select(
                'employee_master.pk',
                'employee_master.first_name',
                'employee_master.last_name',
                'employee_master.email',
                'employee_master.mobile',
                'employee_master.office_extension_no',
                'employee_master.profile_picture',
                'employee_master.dob',
                'designation_master.designation_name'
            )
            ->get();

        $myEmployeePk = Auth::user()->user_id ?? null;
        $isMyBirthday = false;
        $myBirthdayWishCount = 0;
        if ($myEmployeePk) {
            $myDob = EmployeeMaster::where('pk', $myEmployeePk)->value('dob');
            if ($myDob) {
                $isMyBirthday = Carbon::parse($myDob)->format('m-d') === now()->format('m-d');
            }
            if ($isMyBirthday) {
                $myBirthdayWishCount = Notification::where('receiver_user_id', $myEmployeePk)
                    ->where('type', 'birthday')
                    ->whereDate('created_at', today())
                    ->count();
            }
        }

        return view('admin.birthday-wish.index', compact(
            'todayBirthdays',
            'isMyBirthday',
            'myBirthdayWishCount'
        ));
    }

    /**
     * JSON: today's birthday wish notifications for the logged-in user (with sender names).
     */
    public function myBirthdayWishesToday(Request $request)
    {
        $myEmployeePk = Auth::user()->user_id ?? null;
        if (! $myEmployeePk) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        $myDob = EmployeeMaster::where('pk', $myEmployeePk)->value('dob');
        if (! $myDob || Carbon::parse($myDob)->format('m-d') !== now()->format('m-d')) {
            return response()->json(['success' => true, 'wishes' => []]);
        }

        $wishes = DB::table('notifications as n')
            ->leftJoin('employee_master as se', 'n.sender_user_id', '=', 'se.pk')
            ->where('n.receiver_user_id', $myEmployeePk)
            ->where('n.type', 'birthday')
            ->whereDate('n.created_at', today())
            ->orderByDesc('n.created_at')
            ->select([
                'n.pk',
                'n.sender_user_id',
                'n.message',
                'n.created_at',
                DB::raw("TRIM(CONCAT(COALESCE(se.first_name,''),' ',COALESCE(se.last_name,''))) as sender_name"),
                'se.email as sender_email',
            ])
            ->get();

        $wishPks = $wishes->pluck('pk')->filter()->map(fn ($pk) => (int) $pk)->values()->all();
        $repliedReferencePks = [];
        if ($wishPks !== []) {
            $repliedReferencePks = Notification::query()
                ->where('type', 'birthday_reply')
                ->where('module_name', 'BirthdayWish')
                ->whereIn('reference_pk', $wishPks)
                ->where('sender_user_id', $myEmployeePk)
                ->pluck('reference_pk')
                ->unique()
                ->all();
        }
        $repliedSet = array_fill_keys($repliedReferencePks, true);

        $wishes->transform(function ($row) use ($repliedSet) {
            $row->already_replied = isset($repliedSet[(int) $row->pk]);

            return $row;
        });

        return response()->json([
            'success' => true,
            'wishes' => $wishes,
        ]);
    }

    /**
     * Reply to someone who sent a birthday wish (in-app notification + email if available).
     */
    public function replyToWish(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'notification_pk' => 'required|integer',
            'message' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()->first()], 422);
        }

        $myEmployeePk = Auth::user()->user_id ?? null;
        if (! $myEmployeePk) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        $wish = Notification::where('pk', $request->integer('notification_pk'))->first();
        if (! $wish || (int) $wish->receiver_user_id !== (int) $myEmployeePk
            || $wish->type !== 'birthday'
        ) {
            return response()->json(['success' => false, 'error' => 'Wish not found.'], 404);
        }

        if (! $wish->sender_user_id || (int) $wish->sender_user_id === (int) $myEmployeePk) {
            return response()->json(['success' => false, 'error' => 'Cannot reply to this wish.'], 422);
        }

        $wishDay = $wish->created_at ? Carbon::parse($wish->created_at)->format('Y-m-d') : null;
        if ($wishDay !== null && $wishDay !== now()->format('Y-m-d')) {
            return response()->json(['success' => false, 'error' => 'This wish is not from today.'], 422);
        }

        $alreadyReplied = Notification::query()
            ->where('type', 'birthday_reply')
            ->where('module_name', 'BirthdayWish')
            ->where('reference_pk', (int) $wish->pk)
            ->where('sender_user_id', $myEmployeePk)
            ->exists();
        if ($alreadyReplied) {
            return response()->json(['success' => false, 'error' => 'You have already replied to this wish.'], 422);
        }

        $senderId = (int) $wish->sender_user_id;
        $replyBody = $request->input('message');
        $u = Auth::user();
        $replierName = $u
            ? trim((string) ($u->first_name ?? '').' '.(string) (data_get($u, 'last_name', '')))
            : '';
        if ($replierName === '') {
            $replierName = $u->name ?? 'Colleague';
        }

        try {
            $notificationService = app(NotificationService::class);
            $notificationService->create(
                receiverUserId: $senderId,
                type: 'birthday_reply',
                moduleName: 'BirthdayWish',
                referencePk: (int) $wish->pk,
                title: 'Reply to your birthday wish',
                message: "{$replierName} replied: {$replyBody}"
            );
        } catch (\Throwable $e) {
            Log::error('BirthdayWishController: reply notification failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'error' => 'Could not send your reply. Try again.'], 500);
        }

        $senderEmail = EmployeeMaster::where('pk', $senderId)->value('email');
        if ($senderEmail) {
            try {
                $emailService = new EmailService('Reply: birthday wish');
                $body = "Hello,\n\n{$replierName} sent you a reply regarding their birthday:\n\n{$replyBody}\n";
                $emailService->sendBulk(collect([$senderEmail]), $body);
            } catch (\Throwable $e) {
                Log::warning('BirthdayWishController: reply email failed', ['error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Your reply was sent.',
        ]);
    }

    public function sendEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'   => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'employee_pk' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()->first()], 422);
        }

        try {
            $emailService = new EmailService($request->input('subject'));
            $failed = $emailService->sendBulk(collect([$request->input('email')]), $request->input('message'));

            if (!empty($failed)) {
                return response()->json(['success' => false, 'error' => 'Failed to deliver email.']);
            }

            // Create in-app birthday notification for the recipient
            $employeePk = $request->input('employee_pk');
            if ($employeePk) {
                $senderName = Auth::user() ? (Auth::user()->first_name ?? Auth::user()->name ?? 'Someone') : 'Someone';
                $this->createBirthdayNotification($employeePk, $senderName);
            }

            return response()->json(['success' => true, 'message' => 'Email sent successfully.']);
        } catch (\Throwable $e) {
            Log::error('BirthdayWishController: sendEmail failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'An error occurred while sending the email.']);
        }
    }

    public function sendBulkEmail(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'recipients'               => 'required|array|min:1',
                'recipients.*.email'       => 'required|email|max:255',
                'recipients.*.name'        => 'required|string|max:255',
                'recipients.*.employee_pk' => 'nullable|integer',
                'subject'                  => 'required|string|max:255',
                'message_template'         => 'required|string|max:5000',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'error' => $validator->errors()->first()], 422);
            }

            $results = ['sent' => 0, 'failed' => 0];
            $notifiedPks = [];

            foreach ($request->input('recipients') as $recipient) {
                $name = (string) ($recipient['name'] ?? '');
                $first = $name !== '' ? explode(' ', $name, 2)[0] : '';
                $personalizedMessage = str_replace(
                    ['{name}', '{first_name}'],
                    [$name, $first],
                    $request->input('message_template')
                );

                try {
                    $emailService = new EmailService($request->input('subject'));
                    $failed = $emailService->sendBulk(collect([$recipient['email']]), $personalizedMessage);
                    if (empty($failed)) {
                        $results['sent']++;
                        if (! empty($recipient['employee_pk'])) {
                            $notifiedPks[] = (int) $recipient['employee_pk'];
                        }
                    } else {
                        $results['failed']++;
                    }
                } catch (\Throwable $e) {
                    $results['failed']++;
                    Log::error('BirthdayWishController: bulk email failed', [
                        'email' => $recipient['email'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if (! empty($notifiedPks)) {
                $senderName = Auth::user() ? (Auth::user()->first_name ?? Auth::user()->name ?? 'Someone') : 'Someone';
                foreach ($notifiedPks as $pk) {
                    $this->createBirthdayNotification($pk, $senderName);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Sent: {$results['sent']}, Failed: {$results['failed']}",
                'results' => $results,
            ]);
        } catch (\Throwable $e) {
            Log::error('BirthdayWishController: sendBulkEmail failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : 'Could not send birthday emails. Check mail configuration (e.g. MAIL_MAILER, SMTP) or try again later.',
            ], 500);
        }
    }

    /**
     * Send only in-app birthday notification (for WhatsApp-only sends or custom sends from dashboard).
     */
    public function sendNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_pks'   => 'required|array|min:1',
            'employee_pks.*' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()->first()], 422);
        }

        $senderName = Auth::user() ? (Auth::user()->first_name ?? Auth::user()->name ?? 'Someone') : 'Someone';
        $count = 0;

        foreach ($request->input('employee_pks') as $pk) {
            $this->createBirthdayNotification((int) $pk, $senderName);
            $count++;
        }

        return response()->json([
            'success' => true,
            'message' => "$count birthday notification(s) sent.",
        ]);
    }

    /**
     * Create a birthday wish notification for the recipient.
     */
    private function createBirthdayNotification(int $employeePk, string $senderName): void
    {
        try {
            $notificationService = app(NotificationService::class);
            $notificationService->create(
                receiverUserId: $employeePk,
                type: 'birthday',
                moduleName: 'BirthdayWish',
                referencePk: $employeePk,
                title: 'Birthday Wish Received!',
                message: "$senderName wished you a Happy Birthday! 🎂"
            );
        } catch (\Throwable $e) {
            Log::error('BirthdayWishController: notification create failed', [
                'employee_pk' => $employeePk,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
