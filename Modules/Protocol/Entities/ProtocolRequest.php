<?php

namespace Modules\Protocol\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use App\Models\{User,EmployeeMaster,DepartmentMaster};

/**
 * The master record for every protocol request, regardless of type.
 * Type-specific fields live on GuestHouseRequest / VehiclePassRequest /
 * TicketRequest, reached through the `requestable` polymorphic relation.
 */
class ProtocolRequest extends Model
{
    use SoftDeletes;

    protected $table = 'protocol_requests';

    protected $fillable = [
        'request_number',
        'batch_id',
        'request_type',
        'requestable_id',
        'requestable_type',
        'user_id',
        'employee_id',
        'employee_department',
        'status',
        'current_stage',
        'protocol_staff_id',
        'recommended_to_id',
        'recommended_at',
        'decided_by_id',
        'decided_at',
    ];

    protected $casts = [
        'recommended_at' => 'datetime',
        'decided_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_RECOMMENDED = 'recommended';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /** The type-specific details (GuestHouseRequest | VehiclePassRequest | TicketRequest) */
    public function requestable(): MorphTo
    {
        return $this->morphTo();
    }

    /** The combined-form submission this request came from. Holds the
     *  shared fields: Purpose, Course Team To Notify, Escort Required,
     *  Faculty, and the Guest Details rows. */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProtocolRequestBatch::class, 'batch_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ProtocolApprovalLog::class, 'protocol_request_id')->orderBy('action_at');
    }

    /** Swap in your host app's User model via config or a morph map if preferred */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'employee_id');
    }

    public function employeeDepartment(): BelongsTo
    {
        return $this->belongsTo(DepartmentMaster::class, 'employee_department');
    }
    public function protocolStaff(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'protocol_staff_id');
    }

    public function recommendedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recommended_to_id');
    }

    /** decided_by_id is an employee_master pk, like protocol_staff_id and the log's action_by_id */
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(EmployeeMaster::class, 'decided_by_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeRecommended($query)
    {
        return $query->where('status', self::STATUS_RECOMMENDED);
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeAwaitingProtocolStaff($query, $protocolStaffId = null)
    {
        $query = $query->where('status', self::STATUS_PENDING);

        return $protocolStaffId
            ? $query->where(function ($q) use ($protocolStaffId) {
                $q->whereNull('protocol_staff_id')->orWhere('protocol_staff_id', $protocolStaffId);
            })
            : $query;
    }

    public function scopeAwaitingManager($query, $managerId)
    {
        return $query->where('status', self::STATUS_RECOMMENDED)
            ->where('recommended_to_id', $managerId);
    }

    /*
    |--------------------------------------------------------------------------
    | Workflow actions
    |--------------------------------------------------------------------------
    | Each action writes the new state on protocol_requests AND appends an
    | immutable row to protocol_approval_logs, which is what the History Log
    | / audit trail is built from.
    */

    /**
     * Protocol Staff approves the request directly — no manager involved.
     */
    public function approveDirectly(int $protocolStaffId, ?string $remarks = null): void
    {
        DB::transaction(function () use ($protocolStaffId, $remarks) {
            $this->lockStatus(self::STATUS_PENDING, 'This request is no longer awaiting Protocol Staff review.');

            $this->update([
                'status' => self::STATUS_APPROVED,
                'current_stage' => 'Completed',
                'decided_by_id' => $protocolStaffId,
                'decided_at' => now(),
            ]);

            $this->logs()->create([
                'action' => 'approved',
                'action_by_id' => $protocolStaffId,
                'action_by_role' => 'Protocol Staff',
                'remarks' => $remarks,
                'action_at' => now(),
            ]);
        });
    }

    /**
     * Protocol Staff recommends the request to a manager/senior staff
     * for final approval.
     */
    public function recommendTo(int $protocolStaffId, int $recommendedToId, ?string $remarks = null): void
    {
        DB::transaction(function () use ($protocolStaffId, $recommendedToId, $remarks) {
            $this->lockStatus(self::STATUS_PENDING, 'This request is no longer awaiting Protocol Staff review.');

            $recommendedToName = self::userDisplayName(User::find($recommendedToId)) ?? "User #{$recommendedToId}";
            $this->update([
                'status' => self::STATUS_RECOMMENDED,
                'current_stage' => "Recommended to {$recommendedToName}",
                'protocol_staff_id' => $protocolStaffId,
                'recommended_to_id' => $recommendedToId,
                'recommended_at' => now(),
            ]);

            $this->logs()->create([
                'action' => 'recommended',
                'action_by_id' => $protocolStaffId,
                'action_by_role' => 'Protocol Staff',
                'recommended_to_id' => $recommendedToId,
                'remarks' => $remarks,
                'action_at' => now(),
            ]);
        });
    }

    /**
     * The recommended manager/staff gives final approval.
     */
    public function approveByManager(int $managerId, ?string $remarks = null): void
    {
        DB::transaction(function () use ($managerId, $remarks) {
            $this->lockStatus(self::STATUS_RECOMMENDED, 'This request is not awaiting your decision.');

            $this->update([
                'status' => self::STATUS_APPROVED,
                'current_stage' => 'Completed',
                'decided_by_id' => $managerId,
                'decided_at' => now(),
            ]);

            $this->logs()->create([
                'action' => 'approved',
                'action_by_id' => $managerId,
                'action_by_role' => 'Manager',
                'remarks' => $remarks,
                'action_at' => now(),
            ]);
        });
    }

    /**
     * The recommended manager/staff rejects the request.
     */
    public function rejectByManager(int $managerId, ?string $remarks = null): void
    {
        DB::transaction(function () use ($managerId, $remarks) {
            $this->lockStatus(self::STATUS_RECOMMENDED, 'This request is not awaiting your decision.');

            $this->update([
                'status' => self::STATUS_REJECTED,
                'current_stage' => 'Completed',
                'decided_by_id' => $managerId,
                'decided_at' => now(),
            ]);

            $this->logs()->create([
                'action' => 'rejected',
                'action_by_id' => $managerId,
                'action_by_role' => 'Manager',
                'remarks' => $remarks,
                'action_at' => now(),
            ]);
        });
    }

    /**
     * Re-read the status under a row lock inside the caller's transaction, so two
     * people deciding the same request cannot both pass the controller's check.
     */
    protected function lockStatus(string $expected, string $message): void
    {
        $status = static::whereKey($this->getKey())->lockForUpdate()->value('status');

        abort_if($status !== $expected, 403, $message);
    }

    /**
     * Called once, right after creation, to record the "Raised" log entry.
     */
    public function logRaised(): void
    {
        $this->logs()->create([
            'action' => 'raised',
            'action_by_id' => $this->employee_id,
            'action_by_role' => 'Employee',
            'remarks' => null,
            'action_at' => $this->created_at ?? now(),
        ]);
    }

    /**
     * Records an employee edit on the timeline.
     */
    public function logUpdated(?string $remarks = null): void
    {
        $this->logs()->create([
            'action' => 'updated',
            'action_by_id' => $this->employee_id,
            'action_by_role' => 'Employee',
            'remarks' => $remarks,
            'action_at' => now(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function typeLabel(): string
    {
        return config("protocol.request_types.{$this->request_type}.label", $this->request_type);
    }

    /** Called inside the store transaction; the lock holds concurrent submissions until it commits */
    public static function generateRequestNumber(): string
    {
        $last = static::withTrashed()->orderByDesc('id')->lockForUpdate()->first();
        $next = $last ? $last->id + 1 : 1001;

        // The empty-table seed and id + 1 are two sequences that meet (PR-1001 again at id 1000), and
        // request_number is unique; when the number is taken, continue after the highest one issued (PR #348 F-028)
        $taken = static::withTrashed()->where('request_number', 'PR-' . $next)->lockForUpdate()->value('id');
        if ($taken !== null) {
            $next = 1 + (int) static::withTrashed()->lockForUpdate()
                ->max(DB::raw('CAST(SUBSTRING(request_number, 4) AS UNSIGNED)'));
        }

        return 'PR-' . $next;
    }

    /**
     * Eager-load the polymorphic `requestable` together with the nested
     * relation that only exists on some of its possible morph targets
     * (VehiclePassRequest::legs, TicketRequest::journeys — GuestHouseRequest
     * has neither). Plain dot-notation eager loading (`with('requestable.legs')`)
     * does not work reliably across a MorphTo when only some target
     * classes define the relation, so this uses morphWith() instead.
     */
    public static function withFullRequestable()
    {
        return static::with(['requestable' => function ($morphTo) {
            $morphTo->morphWith([
                VehiclePassRequest::class => ['legs'],
                TicketRequest::class => ['journeys'],
            ]);
        }]);
    }

    /**
     * Instance-level equivalent of withFullRequestable(), for lazy-loading
     * onto a model that's already been fetched (route-model-bound
     * controller methods use this instead of the query-builder version).
     */
    public function loadFullRequestable(): static
    {
        return $this->load(['requestable' => function ($morphTo) {
            $morphTo->morphWith([
                VehiclePassRequest::class => ['legs'],
                TicketRequest::class => ['journeys'],
            ]);
        }]);
    }

    /*
    |--------------------------------------------------------------------------
    | Convenience accessors — proxy through to the batch so views can
    | keep referencing $protocolRequest->purpose / ->guests / etc.
    | without reaching through ->batch-> every time.
    |--------------------------------------------------------------------------
    */

    public function getPurposeAttribute(): ?string
    {
        return $this->batch?->purpose;
    }

    public function getCourseTeamToNotifyAttribute(): ?string
    {
        return $this->batch?->course_team_to_notify;
    }

    public function getEscortRequiredAttribute(): bool
    {
        return (bool) $this->batch?->escort_required;
    }

    public function getIsFacultyAttribute(): bool
    {
        return (bool) $this->batch?->is_faculty;
    }

    /**
     * Rows recommended before the name fix stored "Recommended to User #<id>";
     * rebuild the label from the approver so those show the name too.
     */
    public function getCurrentStageAttribute($value): ?string
    {
        if ($this->status === self::STATUS_RECOMMENDED && $this->recommended_to_id && str_contains((string) $value, 'User #')) {
            $name = self::userDisplayName($this->recommendedTo);
            return $name ? "Recommended to {$name}" : $value;
        }

        return $value;
    }

    /** user_credentials has no name column — it is first_name + last_name */
    public static function userDisplayName(?User $user): ?string
    {
        $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        return $name !== '' ? $name : null;
    }
}
