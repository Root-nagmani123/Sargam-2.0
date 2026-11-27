<?php

namespace Modules\GuestTaClaim\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\GuestTaClaim\Enums\ClaimStatus;
use Modules\GuestTaClaim\Enums\Permission;
use Modules\GuestTaClaim\Services\PermissionService;

class Claim extends Model
{
    protected $table = 'gtc_claims';

    protected $guarded = ['id', 'claim_no', 'status'];

    protected $casts = [
        'status' => ClaimStatus::class,
        'period_from' => 'date',
        'period_to' => 'date',
        'honorarium_rate' => 'decimal:2',
        'basic_pay' => 'decimal:2',
        'travel_fare' => 'decimal:2',
        'local_conveyance' => 'decimal:2',
        'da_rate' => 'decimal:2',
        'da_percentage' => 'integer',
        'honorarium_amount' => 'decimal:2',
        'da_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'forwarded_at' => 'datetime',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'report_generated_at' => 'datetime',
    ];

    public function sessions(): HasMany
    {
        return $this->hasMany(ClaimSession::class)->orderBy('session_date')->orderBy('start_time');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ClaimHistory::class)->orderBy('id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ClaimAttachment::class);
    }

    public function travellingAllowances(): HasMany
    {
        return $this->hasMany(ClaimTravellingAllowance::class)->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('guest-ta-claim.user_model'), 'created_by');
    }

    /** Claims a user is allowed to see, based on their mapped permissions. */
    public function scopeVisibleTo(Builder $query, $user): Builder
    {
        $perms = app(PermissionService::class);

        if ($perms->hasAny($user, [Permission::CreateClaim, Permission::Forward, Permission::GenerateReport])) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user, $perms) {
            $q->whereRaw('1 = 0');
            if ($perms->has($user, Permission::Approve)) {
                $q->orWhere(function (Builder $cc) use ($user) {
                    $cc->where('status', '!=', ClaimStatus::Draft->value);
                    if (config('guest-ta-claim.restrict_approval_to_course_coordinator')) {
                        $cc->where(fn ($w) => $w->where('coordinator_user_id', $user->getKey())->orWhereNull('coordinator_user_id'));
                    }
                });
            }
            if ($perms->hasAny($user, [Permission::ViewPayments, Permission::MarkPaid])) {
                $q->orWhereIn('status', [ClaimStatus::PendingAccounts->value, ClaimStatus::Paid->value]);
            }
        });
    }

    public function scopeForCoordinator(Builder $query, $user): Builder
    {
        // if (config('guest-ta-claim.restrict_approval_to_course_coordinator')
        //     && ! app(PermissionService::class)->isSuperAdmin($user)) {
        //     $query->where(fn ($w) => $w->where('coordinator_user_id', $user->getKey())->orWhereNull('coordinator_user_id'));
        // }
        $query->where(fn ($w) => $w->where('coordinator_user_id', $user->getKey())->orWhereNull('coordinator_user_id'));

        return $query;
    }

    public function sessionsTaken(): int
    {
        return $this->sessions->where('status', 'taken')->count();
    }

    public function amountPaidOrClaimed(): float
    {
        return (float) ($this->paid_amount ?? $this->total_amount);
    }
}