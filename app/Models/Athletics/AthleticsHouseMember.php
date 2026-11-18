<?php

namespace App\Models\Athletics;

use App\Models\CourseMaster;
use App\Models\StudentMaster;
use Illuminate\Database\Eloquent\Model;

class AthleticsHouseMember extends Model
{
    protected $table = 'athletics_house_members';

    protected $fillable = ['house_id', 'course_master_pk', 'student_master_pk'];

    public function house()
    {
        return $this->belongsTo(AthleticsHouse::class, 'house_id');
    }

    public function student()
    {
        return $this->belongsTo(StudentMaster::class, 'student_master_pk', 'pk');
    }

    public function course()
    {
        return $this->belongsTo(CourseMaster::class, 'course_master_pk', 'pk');
    }

    /** student_master_pk => house, for every OT placed in a house for this course. */
    public static function houseMapForCourse(int $coursePk)
    {
        return static::with('house:id,name')
            ->where('course_master_pk', $coursePk)
            ->get()
            ->mapWithKeys(fn ($m) => [(int) $m->student_master_pk => $m->house]);
    }
}
