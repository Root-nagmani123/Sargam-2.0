<?php

namespace App\Models\COE;

use App\Models\CourseMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One roll-number generation run: the course + batch it covers and the format and rules
 * the administrator configured for it.
 *
 * `$fillable` is explicit rather than `$guarded = []`. status / frozen_at / frozen_by
 * decide whether a roll number can still be edited, so they are set by the service that
 * owns the transition and never by whatever the request happened to carry.
 */
class RollNumberAllocation extends Model
{
    /** Generated, still editable. */
    public const STATUS_GENERATED = 1;

    /** Frozen — the doc's "once frozen, roll numbers cannot be changed by ordinary users". */
    public const STATUS_FROZEN = 2;

    /**
     * How the Generate screen decides the order participants are numbered in.
     *
     * The first two honour the allocation rules; the sequential ones deliberately do not,
     * because ordering BY service is the exact opposite of keeping services apart.
     * RollNumberAllocator::appliesRules() is the single place that distinction is made.
     */
    public const STRATEGY_ALLOCATION_RULES = 'allocation_rules';
    public const STRATEGY_RANDOM_WITH_RULES = 'random_with_rules';
    public const STRATEGY_SEQUENTIAL_BY_SERVICE = 'sequential_by_service';
    public const STRATEGY_SEQUENTIAL_BY_NAME = 'sequential_by_name';

    /**
     * The one strategy that does not number positionally: the serial IS the digits of the
     * participant's OT code, so A01 becomes 001 and D31 becomes 031. That is why the
     * Generate screen hides Starting Number for it — there is no position to start from,
     * and a start would only shift the numbers away from the codes they are meant to
     * mirror. See RollNumberAllocator::derivesSerialFromOtCode().
     */
    public const STRATEGY_SEQUENTIAL_BY_OT_CODE = 'sequential_by_ot_code';

    /**
     * Strategy key => the label the screen shows. One list, so the dropdown, the
     * validation whitelist and any report all name them identically.
     *
     * @return array<string, string>
     */
    public static function strategies(): array
    {
        return [
            self::STRATEGY_ALLOCATION_RULES => 'Allocation Rules',
            self::STRATEGY_RANDOM_WITH_RULES => 'Random with Rules',
            self::STRATEGY_SEQUENTIAL_BY_SERVICE => 'Sequential by Service',
            self::STRATEGY_SEQUENTIAL_BY_NAME => 'Sequential by Name',
            self::STRATEGY_SEQUENTIAL_BY_OT_CODE => 'Sequential by OT Code',
        ];
    }

    public static function strategyLabel(?string $key): string
    {
        return self::strategies()[$key] ?? 'Allocation Rules';
    }

    protected $table = 'coe_roll_number_allocations';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    protected $fillable = [
        'course_master_pk',
        'batch',
        'prefix',
        'start_number',
        'number_padding',
        'number_separator',
        'strategy',
        'rule_avoid_same_service',
        'rule_avoid_consecutive_theatre',
        'rule_alternate_gender',
        'participant_count',
        'generated_at',
        'created_by',
        'created_date',
        'modified_by',
        'modified_date',
    ];

    protected $casts = [
        'course_master_pk' => 'integer',
        'batch' => 'integer',
        'start_number' => 'integer',
        'number_padding' => 'integer',
        'rule_avoid_same_service' => 'boolean',
        'rule_avoid_consecutive_theatre' => 'boolean',
        'rule_alternate_gender' => 'boolean',
        'participant_count' => 'integer',
        'status' => 'integer',
        'generated_at' => 'datetime',
        'frozen_at' => 'datetime',
        'created_date' => 'datetime',
        'modified_date' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(CourseMaster::class, 'course_master_pk', 'pk');
    }

    public function rollNumbers(): HasMany
    {
        return $this->hasMany(RollNumber::class, 'roll_number_allocation_pk', 'pk');
    }

    public function isFrozen(): bool
    {
        return (int) $this->status === self::STATUS_FROZEN;
    }

    /**
     * Render one participant's roll number, e.g. FC101-001.
     *
     * str_pad only ever adds digits, so a serial that has outgrown the configured
     * padding keeps all of its digits rather than being truncated into a duplicate.
     */
    public function formatRollNumber(int $serial): string
    {
        return $this->prefix
            . $this->number_separator
            . str_pad((string) $serial, (int) $this->number_padding, '0', STR_PAD_LEFT);
    }
}
