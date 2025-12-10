<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseWiseOTList extends Model
{
    use HasFactory;

    protected $table = 'course_wise_ot_list';
    
    protected $fillable = [
        'course_master_pk',
        'student_master_pk',
        'generated_ot_code',
        'active_inactive',
        'created_date',
        'updated_date'
    ];

    public $timestamps = false;

    // Relationships
    public function course()
    {
        return $this->belongsTo(CourseMaster::class, 'course_master_pk');
    }

    public function student()
    {
        return $this->belongsTo(StudentMaster::class, 'student_master_pk');
    }

    public function enrollment()
    {
        return $this->belongsTo(StudentMasterCourseMap::class, 'student_master_pk', 'student_master_pk')
                    ->where('course_master_pk', $this->course_master_pk);
    }
}