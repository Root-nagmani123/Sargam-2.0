<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One row per thing a notice is addressed to.
 *
 * A notice's whole audience lives here — the courses / departments it targets,
 * the groups within them, and any individually-picked people. The scalar
 * columns still on notices_notification mirror the single-value case only and
 * are not read back by this module.
 */
class NoticeAudienceMap extends Model
{
    public const TYPE_COURSE = 'C';
    public const TYPE_GROUP = 'G';
    public const TYPE_DEPARTMENT = 'D';
    public const TYPE_STUDENT = 'S';
    public const TYPE_EMPLOYEE = 'E';

    protected $table = 'notice_audience_map';
    protected $primaryKey = 'pk';
    public $timestamps = false;
    protected $guarded = [];

    public function notice()
    {
        return $this->belongsTo(NoticeNotification::class, 'notices_notification_pk', 'pk');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('audience_type', $type);
    }

    public function scopeCourses($query)
    {
        return $query->where('audience_type', self::TYPE_COURSE);
    }

    public function scopeGroups($query)
    {
        return $query->where('audience_type', self::TYPE_GROUP);
    }

    public function scopeDepartments($query)
    {
        return $query->where('audience_type', self::TYPE_DEPARTMENT);
    }

    public function scopeStudents($query)
    {
        return $query->where('audience_type', self::TYPE_STUDENT);
    }

    public function scopeEmployees($query)
    {
        return $query->where('audience_type', self::TYPE_EMPLOYEE);
    }
}
