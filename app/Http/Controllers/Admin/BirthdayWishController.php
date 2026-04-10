<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeMaster;
use App\Services\Messaging\EmailService;
use App\Services\NotificationService;
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

        return view('admin.birthday-wish.index', compact('todayBirthdays'));
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
        $validator = Validator::make($request->all(), [
            'recipients'             => 'required|array|min:1',
            'recipients.*.email'     => 'required|email|max:255',
            'recipients.*.name'      => 'required|string|max:255',
            'recipients.*.employee_pk' => 'nullable|integer',
            'subject'                => 'required|string|max:255',
            'message_template'       => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()->first()], 422);
        }

        $results = ['sent' => 0, 'failed' => 0];
        $notifiedPks = [];

        foreach ($request->input('recipients') as $recipient) {
            $personalizedMessage = str_replace(
                ['{name}', '{first_name}'],
                [$recipient['name'], explode(' ', $recipient['name'])[0]],
                $request->input('message_template')
            );

            try {
                $emailService = new EmailService($request->input('subject'));
                $failed = $emailService->sendBulk(collect([$recipient['email']]), $personalizedMessage);
                if (empty($failed)) {
                    $results['sent']++;
                    if (!empty($recipient['employee_pk'])) {
                        $notifiedPks[] = (int) $recipient['employee_pk'];
                    }
                } else {
                    $results['failed']++;
                }
            } catch (\Throwable $e) {
                $results['failed']++;
                Log::error('BirthdayWishController: bulk email failed', [
                    'email' => $recipient['email'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Create in-app birthday notifications for all successfully emailed recipients
        if (!empty($notifiedPks)) {
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
