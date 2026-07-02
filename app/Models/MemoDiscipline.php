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

    protected $fillable = [
        'course_master_pk',
        'discipline_master_pk',
        'student_master_pk',
        'date',
        'mark_deduction_submit',
        'final_mark_deduction',
        'remarks',
        'status',
        'conclusion_type_pk',
        'conclusion_remark',
        'memo_notice_template_pk',
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
