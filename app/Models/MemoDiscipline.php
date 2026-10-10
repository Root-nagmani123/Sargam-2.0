<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MemoDiscipline extends Model
{
    use HasFactory;

    protected $table = 'discipline_memo_status';
    protected $primaryKey = 'pk';
    public $timestamps = false; // created_date / modified_date manual hain

    /** status: 1 Recorded, 2 Memo Sent, 3 Closed (see MemoDisciplineController). */
    public const STATUS_CLOSED = 3;

    /**
     * Marks actually deducted from this OT — the dashboard's "Total Marks Deducted
     * in Discipline".
     *
     * Only CLOSED memos count, and only their final_mark_deduction. A memo that is
     * merely Recorded or Sent carries mark_deduction_submit, which is what was
     * proposed, not what was taken: closeMemo() writes final_mark_deduction at
     * conclusion and the incharge can settle on a different figure — 5 of the rows
     * on record differ from what was submitted, and an exonerating conclusion
     * closes at nothing at all. Summing the proposal would overstate the penalty.
     *
     * final_mark_deduction is a varchar column, so it is cast before summing
     * rather than left to an implicit conversion.
     */
    public static function totalMarksDeductedFor(int $studentPk): float
    {
        return (float) static::query()
            ->where('student_master_pk', $studentPk)
            ->where('status', self::STATUS_CLOSED)
            ->sum(\Illuminate\Support\Facades\DB::raw('CAST(COALESCE(final_mark_deduction, 0) AS DECIMAL(10,2))'));
    }

    protected $fillable = [
        'course_master_pk',
        'discipline_master_pk',
        'student_master_pk',
        'date',
        'mark_deduction_submit',
        'final_mark_deduction',
        'minor_major',
        'remarks',
        'status',
        'conclusion_type_pk',
        'conclusion_remark',
        'memo_notice_template_pk',
        'template_snapshot',
        'created_date',
        'modified_date'
    ];

    /* ================= Relations ================= */

    public function course()
    {
        return $this->belongsTo(CourseMaster::class, 'course_master_pk', 'pk');
    }

    public function discipline()
    {
        return $this->belongsTo(DisciplineMaster::class, 'discipline_master_pk', 'pk');
    }

    public function student()
    {
        return $this->belongsTo(StudentMaster::class, 'student_master_pk', 'pk');
    }
    public function messages()
{
    return $this->hasMany(
        DisciplineMessageStudentDecipIncharge::class,
        'discipline_memo_status_pk',
        'pk'
    )->orderBy('created_date', 'asc');
}

public function template()
{
    return $this->hasOne(MemoNoticeTemplate::class, 'course_master_pk', 'course_master_pk')
        ->where('memo_notice_type', 'Discipline Memo');
}

/**
 * The exact template chosen at send time (pinned). Falls back to the course-level
 * template() when a memo predates this feature.
 */
public function chosenTemplate()
{
    return $this->belongsTo(MemoNoticeTemplate::class, 'memo_notice_template_pk', 'pk');
}

/**
 * Effective template for display: the pinned one if set, else the course-level default.
 */
public function getResolvedTemplateAttribute()
{
    return $this->chosenTemplate ?: $this->template;
}

}
