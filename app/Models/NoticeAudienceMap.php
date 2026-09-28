<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One row per individually-picked recipient of a notice.
 *
 * Only written when the notice's audience_mode is "individual" — an "all" or
 * group-wide notice resolves its recipients from the course/department/group
 * columns on the notice itself.
 */
class NoticeAudienceMap extends Model
{
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

    public function scopeStudents($query)
    {
        return $query->where('audience_type', self::TYPE_STUDENT);
    }

    public function scopeEmployees($query)
    {
        return $query->where('audience_type', self::TYPE_EMPLOYEE);
    }
}
