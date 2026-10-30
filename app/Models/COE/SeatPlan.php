<?php

namespace App\Models\COE;

use App\Models\CourseMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One automatic room-allocation run for a course+batch.
 *
 * `$fillable` is explicit rather than `$guarded = []`. status / allocated_at /
 * published_at and the two counts describe what the allocator did and whether
 * participants can see their seat, so they move through the action that owns the
 * transition, never through whatever the request carried.
 */
class SeatPlan extends Model
{
    /** Venues are being chosen; no seats exist yet. */
    public const STATUS_DRAFT = 1;

    /** Seats assigned, administrator-only — the doc's "Assigned / Not published". */
    public const STATUS_ALLOCATED = 2;

    /** Participants can see their room and seat (the doc's "VENUE NOTIFICATION"). */
    public const STATUS_PUBLISHED = 3;

    /**
     * How an OT's laptop status is turned into "needs a laptop-capable desk".
     *
     * The declaration asks "Is a laptop required?", which does not by itself answer the
     * seating question — that depends on the examination. Keeping the interpretation as a
     * stored choice is the doc's "configuration-driven, not hard-coded" principle applied
     * to this screen.
     */
    public const LAPTOP_REQ_DECLARED_YES = 1;
    public const LAPTOP_REQ_DECLARED_NO = 2;
    public const LAPTOP_REQ_ALL = 3;
    public const LAPTOP_REQ_NONE = 4;

    /** coe_seat_plans.candidate_category — which participants of the course+batch are seated. */
    public const CATEGORY_ALL = 0;
    public const CATEGORY_GENERAL = 1;
    public const CATEGORY_PH = 2;

    protected $table = 'coe_seat_plans';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    protected $fillable = [
        'plan_name',
        'course_master_pk',
        'batch',
        'examination_drive_component_map_id',
        'candidate_category',
        'exam_name',
        'subject_name',
        'exam_date',
        'exam_start_time',
        'laptop_requirement',
        'rule_split_by_laptop',
        'rule_balance_rooms',
        'rule_skip_alternate_seats',
        'rule_odd_even',
        'seat_gap',
        'seat_start_number',
        'created_by',
        'created_date',
        'modified_by',
        'modified_date',
    ];

    protected $casts = [
        'course_master_pk' => 'integer',
        'batch' => 'integer',
        'exam_date' => 'date',
        'laptop_requirement' => 'integer',
        'rule_split_by_laptop' => 'boolean',
        'rule_balance_rooms' => 'boolean',
        'rule_skip_alternate_seats' => 'boolean',
        'examination_drive_component_map_id' => 'integer',
        'candidate_category' => 'integer',
        'rule_odd_even' => 'boolean',
        'seat_gap' => 'integer',
        'seat_start_number' => 'integer',
        'allocated_count' => 'integer',
        'unallocated_count' => 'integer',
        'status' => 'integer',
        'allocated_at' => 'datetime',
        'published_at' => 'datetime',
        'created_date' => 'datetime',
        'modified_date' => 'datetime',
    ];

    /**
     * Status key => the label the screen shows. One list, so the badge, the filter
     * dropdown and the create form all name them identically.
     *
     * @return array<int, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_ALLOCATED => 'Allocated',
            self::STATUS_PUBLISHED => 'Published',
        ];
    }

    public static function statusLabel(?int $status): string
    {
        return self::statuses()[$status] ?? 'Draft';
    }

    /**
     * Laptop-requirement key => the label the form shows.
     *
     * Worded as what it does to the seating, not as what the trainee answered — the
     * administrator is choosing a seating policy here, not restating the question.
     *
     * @return array<int, string>
     */
    public static function laptopRequirements(): array
    {
        return [
            self::LAPTOP_REQ_DECLARED_NO => 'Participants who answered Laptop Required = No take laptop seats',
            self::LAPTOP_REQ_DECLARED_YES => 'Participants who answered Laptop Required = Yes need a laptop seat',
            self::LAPTOP_REQ_ALL => 'Every participant needs a laptop seat (fully online examination)',
            self::LAPTOP_REQ_NONE => 'Nobody needs a laptop seat (pen-and-paper examination)',
        ];
    }

    public static function laptopRequirementLabel(?int $value): string
    {
        return self::laptopRequirements()[$value] ?? self::laptopRequirements()[self::LAPTOP_REQ_DECLARED_NO];
    }

    /**
     * Candidate-category key => label. PH is read from the disability answer on the
     * laptop status screen; there is no other disability field on student_master.
     *
     * @return array<int, string>
     */
    public static function categories(): array
    {
        return [
            self::CATEGORY_ALL => 'All Candidates',
            self::CATEGORY_GENERAL => 'General Category',
            self::CATEGORY_PH => 'PH Category',
        ];
    }

    public static function categoryLabel(?int $value): string
    {
        return self::categories()[$value] ?? self::categories()[self::CATEGORY_ALL];
    }

    /** Desks the allocator steps by: 1 = every desk, 2 = every other desk, … */
    public function seatStep(): int
    {
        return max($this->rule_skip_alternate_seats ? 2 : 1, (int) $this->seat_gap + 1);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(CourseMaster::class, 'course_master_pk', 'pk');
    }

    public function venues(): HasMany
    {
        return $this->hasMany(SeatPlanVenue::class, 'seat_plan_pk', 'pk');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(SeatAllocation::class, 'seat_plan_pk', 'pk');
    }

    public function isDraft(): bool
    {
        return (int) $this->status === self::STATUS_DRAFT;
    }

    public function isAllocated(): bool
    {
        return (int) $this->status === self::STATUS_ALLOCATED;
    }

    public function isPublished(): bool
    {
        return (int) $this->status === self::STATUS_PUBLISHED;
    }

    /**
     * Does this plan read the OT laptop status at all?
     *
     * LAPTOP_REQ_NONE and LAPTOP_REQ_ALL answer the seating question without asking
     * anybody, so for those the declarations are never consulted and a cohort with no
     * answers on record is not a problem the screens need to report.
     */
    public function readsLaptopStatus(): bool
    {
        return in_array((int) $this->laptop_requirement, [
            self::LAPTOP_REQ_DECLARED_YES,
            self::LAPTOP_REQ_DECLARED_NO,
        ], true);
    }

    /**
     * Can the allocator run right now, and if not, why?
     *
     * Returned as a message rather than a bool so the button and the refusal say the same
     * thing — a disabled Allocate with the reason hidden in a title attribute reads as a
     * broken feature. Null means "go ahead".
     */
    public function blockedFromAllocating(int $venueCount, int $participantCount): ?string
    {
        if ($this->isPublished()) {
            return 'This plan is published. Move it back to Allocated before re-running, or participants would see their seat change underneath them.';
        }

        if ($venueCount === 0) {
            return 'Add at least one examination venue before allocating.';
        }

        if ($participantCount === 0) {
            return 'This course and batch has no participants with a roll number yet. Allocate roll numbers first.';
        }

        return null;
    }

    /**
     * The transitions available from where this plan is now, as buttons.
     *
     * Kept here rather than in the view so the detail page cannot offer a move the
     * controller would reject, and so the wording of a transition lives in one place.
     * Draft is deliberately absent as a target: a plan with seats in it is not a draft,
     * and the way back is to delete the seats, which Re-allocate already does.
     *
     * @return array<int, array{label: string, icon: string, primary: bool}>
     */
    public function nextStatuses(): array
    {
        $publish = ['label' => 'Publish to Participants', 'icon' => 'bi-send', 'primary' => true];
        $unpublish = ['label' => 'Unpublish', 'icon' => 'bi-eye-slash', 'primary' => false];

        return match ((int) $this->status) {
            self::STATUS_ALLOCATED => [self::STATUS_PUBLISHED => $publish],
            self::STATUS_PUBLISHED => [self::STATUS_ALLOCATED => $unpublish],
            // A draft has no seats to show anybody, so it offers no transition at all —
            // Allocate is the action that moves it on, and that is not a status change.
            default => [],
        };
    }
}
