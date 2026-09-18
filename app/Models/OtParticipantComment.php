<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A comment / feedback left on an OT from the OT / Participants List.
 *
 * See the create migration for why this is its own table rather than a reuse of
 * student_medical_exemption_comments or the session-feedback tables.
 */
class OtParticipantComment extends Model
{
    protected $table = 'ot_participant_comment';
    protected $guarded = [];
    protected $primaryKey = 'pk';

    // The table carries created_date / modified_date, not Laravel's created_at pair.
    public $timestamps = false;

    protected $casts = [
        'notify_ot' => 'int',
        'active_inactive' => 'int',
        'comment_date' => 'date',
    ];

    public function studentMaster()
    {
        return $this->belongsTo(StudentMaster::class, 'student_master_pk', 'pk');
    }

    public function course()
    {
        return $this->belongsTo(CourseMaster::class, 'course_master_pk', 'pk');
    }

    /** Only rows that have not been soft-removed. */
    public function scopeActive($query)
    {
        return $query->where('active_inactive', 1);
    }
}
