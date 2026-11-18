<?php

namespace App\Models\Athletics;

use App\Models\StudentMaster;
use Illuminate\Database\Eloquent\Model;

class AthleticsNomination extends Model
{
    protected $table = 'athletics_nominations';

    protected $fillable = ['drive_id', 'student_master_pk', 'status', 'created_by'];

    public const STATUSES = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];

    public function drive()
    {
        return $this->belongsTo(AthleticsDrive::class, 'drive_id');
    }

    public function student()
    {
        return $this->belongsTo(StudentMaster::class, 'student_master_pk', 'pk');
    }

    public function events()
    {
        return $this->belongsToMany(AthleticsEvent::class, 'athletics_nomination_events', 'nomination_id', 'event_id');
    }
}
