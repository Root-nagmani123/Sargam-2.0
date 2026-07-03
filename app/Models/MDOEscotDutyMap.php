<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MDOEscotDutyMap extends Model
{
    protected $table = "mdo_escot_duty_map";
    protected $guarded = [];

    public $timestamps = false;

    protected $primaryKey = 'pk';

    /** Duty acknowledgement statuses */
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';

    protected $casts = [
        'acknowledged_at' => 'datetime',
    ];

    /** True when the duty is still awaiting OT acknowledgement. */
    public function isPending(): bool
    {
        return ($this->duty_status ?? self::STATUS_PENDING) !== self::STATUS_COMPLETED;
    }

    public static function getMdoDutyTypes(): array
    {
        return [
            'mdo' => optional(MDODutyTypeMaster::whereRaw('LOWER(mdo_duty_type_name) = ?', ['mdo'])->first())->pk,
            'escort' => optional(MDODutyTypeMaster::whereRaw('LOWER(mdo_duty_type_name) = ?', ['escort'])->first())->pk,
            'other' => optional(MDODutyTypeMaster::whereRaw('LOWER(mdo_duty_type_name) = ?', ['other'])->first())->pk,
        ];
    }

    public function courseMaster()
    {
        return $this->belongsTo(CourseMaster::class, 'course_master_pk', 'pk');
    }
    public function mdoDutyTypeMaster()
    {
        return $this->belongsTo(MDODutyTypeMaster::class, 'mdo_duty_type_master_pk', 'pk');
    }

    public function studentMaster()
    {
        return $this->belongsTo(StudentMaster::class, 'selected_student_list', 'pk');
    }

    public function studentMasterCourseMap()
    {
        return $this->hasMany(StudentMasterCourseMap::class, 'course_master_pk', 'course_master_pk');
    }

    public function facultyMaster()
    {
        return $this->belongsTo(FacultyMaster::class, 'faculty_master_pk', 'pk');
    }

    /**
     * All faculty pks for this duty (multiple supported). Falls back to the single
     * faculty_master_pk for legacy rows that predate the faculty_master_pks column.
     *
     * @return int[]
     */
    public function facultyPks(): array
    {
        if (!empty($this->faculty_master_pks)) {
            return array_values(array_filter(array_map('intval', explode(',', $this->faculty_master_pks))));
        }

        return $this->faculty_master_pk ? [(int) $this->faculty_master_pk] : [];
    }

    /**
     * Comma-separated faculty names for display (resolves all selected faculty).
     */
    public function getFacultyNamesAttribute(): string
    {
        $pks = $this->facultyPks();
        if (empty($pks)) {
            return '';
        }

        return FacultyMaster::whereIn('pk', $pks)
            ->orderBy('full_name')
            ->pluck('full_name')
            ->implode(', ');
    }
}
