<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountDeletionRequest as Req;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Two-stage decision on account-deletion requests. The route picks the stage (route default 'stage'):
 *  - review:  the reviewer (Computer Section) forwards a pending request as verified, or rejects it;
 *  - approve: the admin gives the final approve / disapprove on a verified request.
 * Each stage acts only on its own status, so neither can skip or redo the other.
 */
class AccountDeletionRequestController extends Controller
{
    private const STAGES = [
        'review' => [
            'title' => 'Account deletion review',
            'route' => 'admin.account-deletion-review',
            'from' => Req::STATUS_PENDING,
            'accept' => Req::STATUS_VERIFIED,
            'accept_label' => 'Verify & forward',
            'reject_label' => 'Reject',
            'remarks' => 'review_remarks', 'by' => 'reviewed_by', 'at' => 'reviewed_at',
        ],
        'approve' => [
            'title' => 'Account deletion requests',
            'route' => 'admin.account-deletion-requests',
            'from' => Req::STATUS_VERIFIED,
            'accept' => Req::STATUS_ACCEPTED,
            'accept_label' => 'Approve',
            'reject_label' => 'Disapprove',
            'remarks' => 'admin_remarks', 'by' => 'decided_by', 'at' => 'decided_at',
        ],
    ];

    public function index(Request $request)
    {
        $stage = $request->route('stage');
        $config = self::STAGES[$stage];
        $statuses = Req::STATUS_LABELS;
        $status = array_key_exists((string) $request->query('status'), $statuses) ? $request->query('status') : null;

        $counts = Req::selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');

        $requests = Req::query()
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('id')
            ->get();

        $names = User::whereIn('pk', $requests->pluck('reviewed_by')->merge($requests->pluck('decided_by'))->filter()->unique())
            ->get(['pk', 'first_name', 'last_name'])
            ->mapWithKeys(fn ($u) => [$u->pk => trim($u->first_name.' '.$u->last_name)]);

        return view('admin.account_deletion_requests.index', compact('stage', 'config', 'statuses', 'status', 'counts', 'requests', 'names'));
    }

    public function decide(Request $request, Req $deletionRequest): JsonResponse
    {
        $config = self::STAGES[$request->route('stage')];
        $data = $request->validate([
            'decision' => ['required', Rule::in(['accept', 'reject'])],
            'remarks' => ['required', 'string', 'max:500'],
        ], ['remarks.required' => 'Remarks are required.']);

        $newStatus = $data['decision'] === 'accept' ? $config['accept'] : Req::STATUS_REJECTED;

        $updated = DB::transaction(function () use ($deletionRequest, $config, $data, $newStatus) {
            // Conditional on the stage's own status: two people deciding at once cannot both win.
            $rows = Req::whereKey($deletionRequest->id)
                ->where('status', $config['from'])
                ->update([
                    'status' => $newStatus,
                    $config['remarks'] => $data['remarks'],
                    $config['by'] => Auth::id(),
                    $config['at'] => now(),
                ]);

            if ($rows && $newStatus === Req::STATUS_ACCEPTED) {
                // Login is refused from now on (LoginController); revoke what outlives a login too.
                DB::table('personal_access_tokens')
                    ->where('tokenable_type', User::class)
                    ->where('tokenable_id', $deletionRequest->user_credentials_pk)
                    ->delete();
                DB::table('user_credentials')
                    ->where('pk', $deletionRequest->user_credentials_pk)
                    ->update(['remember_token' => null]);
            }

            return $rows;
        });

        if (! $updated) {
            return response()->json(['message' => 'This request is no longer waiting at this stage.'], 422);
        }

        if ($newStatus === Req::STATUS_ACCEPTED) {
            // After commit, so a concurrent request cannot re-cache the old list: open sessions end on their next request.
            Cache::forget(Req::CLOSED_CACHE_KEY);
        }

        // Verified -> bell to the approvers; accepted / rejected -> email to the requester. Each no-ops otherwise.
        $deletionRequest->refresh()->notifyNextStage();
        $deletionRequest->emailRequesterOutcome();

        return response()->json(['message' => "Request {$deletionRequest->code}: ".Req::STATUS_LABELS[$newStatus].'.']);
    }
}
