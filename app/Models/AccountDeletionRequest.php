<?php

namespace App\Models;

use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class AccountDeletionRequest extends Model
{
    /** Waiting for the reviewer (Computer Section). */
    public const STATUS_PENDING = 'pending';

    /** Reviewer verified it; waiting for the admin's final decision. */
    public const STATUS_VERIFIED = 'verified';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    /** Still in progress: the user may not file another request. */
    public const OPEN_STATUSES = [self::STATUS_PENDING, self::STATUS_VERIFIED];

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Awaiting review',
        self::STATUS_VERIFIED => 'Awaiting approval',
        self::STATUS_ACCEPTED => 'Accepted',
        self::STATUS_REJECTED => 'Rejected',
    ];

    public const REASON_OTHER = 'Other (Please specify).';

    public const REASONS = [
        'No longer associated with LBSNAA.',
        'Course / Training Programme completed.',
        'Transferred to another department / organisation.',
        'Retired from Government Service.',
        'Duplicate account created.',
        'Account created by mistake.',
        'Unable to access or use the account.',
        'Privacy or personal data concerns.',
        'No longer require SARGAM 2.0 services.',
        'Account is no longer active.',
        'Technical issues with the account.',
        self::REASON_OTHER,
    ];

    protected $fillable = [
        'user_credentials_pk', 'user_name', 'full_name', 'email', 'mobile',
        'reason', 'comments', 'status',
        'review_remarks', 'reviewed_by', 'reviewed_at', 'admin_remarks', 'decided_by', 'decided_at',
    ];

    protected $casts = ['reviewed_at' => 'datetime', 'decided_at' => 'datetime'];

    /** "DR-001" style reference shown to the user and the admin. */
    public function getCodeAttribute(): string
    {
        return 'DR-'.str_pad((string) $this->id, 3, '0', STR_PAD_LEFT);
    }

    /** Cache key of closedAccountPks(); forget it whenever a request becomes (or stops being) accepted. */
    public const CLOSED_CACHE_KEY = 'account_deletion.closed_pks';

    /**
     * user_credentials.pk of every account closed by an approved deletion, keyed by pk for isset().
     * Read on every signed-in request (LogoutClosedAccount), so it is cached; the approval clears the
     * cache at once, and the 5-minute TTL bounds staleness for anything that bypasses that (another
     * app server's cache, a manual DB edit). Falls back to the DB if the cache store is unavailable.
     *
     * @return array<int, true>
     */
    public static function closedAccountPks(): array
    {
        $load = fn () => Schema::hasTable('account_deletion_requests')
            ? static::where('status', self::STATUS_ACCEPTED)->pluck('user_credentials_pk')
                ->mapWithKeys(fn ($pk) => [(int) $pk => true])->all()
            : [];

        try {
            return Cache::remember(self::CLOSED_CACHE_KEY, 300, $load);
        } catch (\Throwable $e) {
            return $load();
        }
    }

    /** True once an administrator has approved deletion: the account may no longer sign in. */
    public static function blocksLogin(int $userPk): bool
    {
        // Login calls this: a deploy that has not migrated yet must not break sign-in.
        return Schema::hasTable('account_deletion_requests') && static::where('user_credentials_pk', $userPk)->where('status', self::STATUS_ACCEPTED)->exists();
    }

    /**
     * Mailer for this feature's emails (OTP + outcome). APP_ENV=local has no reachable SMTP, so there the
     * mail is written to storage/logs instead - read the OTP from the log to test the flow. Every other
     * environment sends through the default mailer configured in .env.
     */
    public static function mailer(): Mailer
    {
        return Mail::mailer(app()->environment('local') ? 'log' : null);
    }

    /**
     * Email the requester the final outcome (accepted, or rejected at either stage), at the address they
     * verified with the OTP. An approved user can no longer sign in, so email is the only channel that
     * reaches them. Never throws - the decision is already saved.
     */
    public function emailRequesterOutcome(): void
    {
        if (! in_array($this->status, [self::STATUS_ACCEPTED, self::STATUS_REJECTED], true) || ! $this->email) {
            return;
        }

        $accepted = $this->status === self::STATUS_ACCEPTED;
        $remarks = $accepted ? $this->admin_remarks : ($this->admin_remarks ?? $this->review_remarks);
        $body = "Dear {$this->full_name},\n\n"
            .($accepted
                ? "Your request {$this->code} to delete your SARGAM 2.0 account ({$this->user_name}) has been approved. "
                    ."Your account is now closed and can no longer be used to sign in.\n\n"
                    .'Official, academic and administrative records required under applicable rules are retained.'
                : "Your request {$this->code} to delete your SARGAM 2.0 account ({$this->user_name}) was not approved. "
                    ."Your account remains active.\n\n"
                    .'You may submit a new request from Account settings > Account deletion after signing in.')
            ."\n\nRemarks: {$remarks}\n\n"
            ."If you did not make this request, contact the Computer Section, LBSNAA.\n\n- SARGAM 2.0, LBSNAA";

        try {
            self::mailer()->raw($body, fn ($mail) => $mail->to($this->email)
                ->subject("SARGAM 2.0 - Account deletion request {$this->code} ".($accepted ? 'approved' : 'not approved')));
        } catch (\Throwable $e) {
            Log::warning('Account deletion outcome email failed.', ['request' => $this->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Bell notification to whoever acts next: reviewers for a pending request, approvers for a verified one.
     * Approvers always include Super Admin (the default approver); reviewers fall back to Super Admin only
     * when no one holds the review permission yet. The message names only the request code: notifications
     * are keyed by user_credentials.user_id, which is not unique, so a copy can land in another account.
     * Never throws - the request itself is already saved.
     */
    public function notifyNextStage(): void
    {
        $stages = [
            self::STATUS_PENDING => ['account_deletion_review', 'Review', 'Account deletion request to review',
                "Request {$this->code} is waiting for your review."],
            self::STATUS_VERIFIED => ['account_deletion_requests', 'Approval', 'Account deletion request to approve',
                "Request {$this->code} has been verified by the Computer Section and is waiting for your approval."],
        ];
        if (! isset($stages[$this->status])) {
            return;
        }
        [$permission, $module, $title, $message] = $stages[$this->status];

        try {
            $receivers = User::permission($permission)->pluck('user_id');
            if ($this->status === self::STATUS_VERIFIED || $receivers->isEmpty()) {
                $receivers = $receivers->merge(User::role('Super Admin')->pluck('user_id'));
            }
            $receivers = $receivers->filter()->unique()
                ->reject(fn ($id) => (int) $id === (int) Auth::user()?->user_id)
                ->values()->all();

            if ($receivers) {
                notification()->createMultiple($receivers, 'account_deletion', $module, $this->id, $title, $message);
            }
        } catch (\Throwable $e) {
            Log::warning('Account deletion notification failed.', ['request' => $this->id, 'error' => $e->getMessage()]);
        }
    }
}
