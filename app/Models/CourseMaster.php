<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseMaster extends Model
{
    protected $table = 'course_master';
    protected $guarded = [];
    protected $primaryKey = 'pk';

    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'Modified_date';

    /**
     * Courses that are still running: enabled AND not yet ended.
     *
     * active_inactive on its own is an enabled/disabled flag, NOT a lifecycle one —
     * nearly every course in the table is enabled, so filtering on it alone returns
     * almost the whole table. A course is only "active" once the end date is taken
     * into account, which is how the rest of the app reads it.
     */
    public function scopeActiveRunning($query)
    {
        return $query->where('active_inactive', 1)->where('end_date', '>=', now()->toDateString());
    }

    /**
     * The exact complement of scopeActiveRunning(): disabled courses AND finished
     * ones. Written as the negation rather than its own condition so the two scopes
     * can never overlap or leave a course in neither bucket.
     */
    public function scopeArchived($query)
    {
        return $query->where(function ($q) {
            $q->where('active_inactive', '!=', 1)
                ->orWhereNull('active_inactive')
                ->orWhere('end_date', '<', now()->toDateString())
                ->orWhereNull('end_date');
        });
    }

    /**
     * Programme picker for the Memo/Notice module: enabled courses that have already
     * started and have not yet ended, limited to the supporting section(s) the
     * signed-in user belongs to.
     *
     * scopeActiveRunning() is deliberately not reused here — it only looks at end_date,
     * so a programme starting next month still comes back, and the module must never
     * offer a course that has not begun (no attendance/timetable exists to memo against).
     *
     * Section scoping comes from course_master.user_role_master_pk ("Supporting Section"
     * on the programme form) matched against the user's Spatie roles. get_Role_by_course()
     * returns [] for Admin / Super Admin / PA — meaning no restriction — and [-1] for a
     * user whose section owns no course, so an empty array must be read as "see all".
     */
    public function scopeRunningForUserSection($query)
    {
        $today = now()->toDateString();

        $query->where('active_inactive', 1)
            ->where('start_year', '<=', $today)
            ->where('end_date', '>=', $today);

        $sectionCourseIds = get_Role_by_course();
        if (! empty($sectionCourseIds)) {
            $query->whereIn('pk', $sectionCourseIds);
        }

        return $query;
    }

    public function courseCordinatorMater()
    {
        return $this->hasMany(CourseCordinatorMaster::class, 'courses_master_pk', 'pk');
    }

    public function studentMaster()
    {
        return $this->hasMany(StudentMaster::class, 'course_master_pk', 'pk');
    }

    public function studentMasterCourseMap()
    {
        return $this->hasMany(StudentMasterCourseMap::class, 'course_master_pk', 'pk');
    }

       public function students()
    {
        return $this->belongsToMany(StudentMaster::class, 'student_master_course_map', 'course_master_pk', 'student_master_pk')
            ->withPivot('active_inactive', 'created_date', 'modified_date');
    }
  
    
}
