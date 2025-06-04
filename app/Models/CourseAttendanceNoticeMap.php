<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class CourseAttendanceNoticeMap extends Model
{
    use HasFactory;

    protected $table = 'course_attendance_notice_map';
    protected $primaryKey = 'pk';

    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'modified_date';
    public $timestamps = true;

    protected $fillable = [
        'course_student_attendance_pk',
        'student_master_pk',
        'ot_code',
        'course_master_pk',
        'notice_memo',
        'msg_count',
        'status',
    ];
}
