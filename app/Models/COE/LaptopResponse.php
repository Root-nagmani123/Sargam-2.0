<?php

namespace App\Models\COE;

use App\Models\StudentMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One Officer Trainee's laptop status — the row behind the "OT Laptop Status" grid.
 *
 * A row exists only once the question has been answered; see the migration for why "no
 * row" is the pending state rather than a nullable column.
 */
class LaptopResponse extends Model
{
    /** Laptop Required = Yes — this OT needs a machine provided, and `reason` says why. */
    public const LAPTOP_REQUIRED_YES = 1;

    /** Laptop Required = No — no institute laptop needed, and no reason is kept. */
    public const LAPTOP_REQUIRED_NO = 0;

    /** OT with Disabilities = Yes — the scriber question is asked, and answered. */
    public const HAS_DISABILITY_YES = 1;

    /** OT with Disabilities = No — the scriber question is not asked, so it has no answer. */
    public const HAS_DISABILITY_NO = 0;

    /** Disability Type — asked only when has_disability is 1, NULL otherwise. */
    public const DISABILITY_TYPE_HANDICAPPED = 1;
    public const DISABILITY_TYPE_BLIND = 2;

    /** Who recorded the answer — the grid's "Updated By" column. */
    public const SOURCE_TRAINEE = 1;
    public const SOURCE_ADMIN = 2;

    protected $table = 'coe_laptop_responses';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    /**
     * `source`, `responded_at` and `responded_by` are absent on purpose: they record WHO
     * answered and how, which is the service's to decide, not the request's.
     */
    protected $fillable = [
        'student_master_pk',
        'laptop_required',
        'reason',
        'has_disability',
        'disability_type',
        'scriber_required',
        'scriber_remarks',
        'primary_scriber_pk',
        'secondary_scriber_pk',
        'additional_remarks',
        'created_by',
        'created_date',
        'modified_by',
        'modified_date',
    ];

    protected $casts = [
        'student_master_pk' => 'integer',
        'laptop_required' => 'boolean',
        'has_disability' => 'boolean',
        'disability_type' => 'integer',
        // Nullable on purpose — NULL is "never asked", which is not the same as No.
        'scriber_required' => 'boolean',
        'source' => 'integer',
        'responded_at' => 'datetime',
        'created_date' => 'datetime',
        'modified_date' => 'datetime',
    ];

    /**
     * Source key => the label the "Updated By" column shows. One list, so the grid, the
     * CSV and the printout name them identically.
     *
     * @return array<int, string>
     */
    public static function sources(): array
    {
        return [
            self::SOURCE_TRAINEE => 'OT',
            self::SOURCE_ADMIN => 'Admin',
        ];
    }

    public static function sourceLabel(?int $source): string
    {
        return self::sources()[$source] ?? 'OT';
    }

    /**
     * Disability Type key => label. One list for the dialog, its validation and the grid.
     *
     * @return array<int, string>
     */
    public static function disabilityTypes(): array
    {
        return [
            self::DISABILITY_TYPE_HANDICAPPED => 'Handicapped',
            self::DISABILITY_TYPE_BLIND => 'Blind',
        ];
    }

    public static function disabilityTypeLabel(?int $type): ?string
    {
        return $type === null ? null : (self::disabilityTypes()[$type] ?? null);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentMaster::class, 'student_master_pk', 'pk');
    }
}
