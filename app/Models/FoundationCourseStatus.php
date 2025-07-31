<?php

// namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;

// class FoundationCourseStatus extends Model
// {
//     use HasFactory;
// }

// app/Models/FoundationCourse.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoundationCourseStatus extends Model
{
    use HasFactory;

    protected $table = 'fc_registration_master';
    protected $primaryKey = 'pk';
    protected $appends = ['full_name'];


    // Define constants for statuses
    const STATUS_NOT_RESPONDED = 0;
    const STATUS_REGISTERED = 1;
    const APPLICATION_EXEMPTION = 2;
    const SUBMISSION_DRAFT = 1;

    // Define fillable fields if you need mass assignment
    protected $guarded = [];

    // Relationships (if you have related tables)
    public function service()
    {
        return $this->belongsTo(ServiceMaster::class, 'service_master_pk', 'pk');
    }


    public function getFullNameAttribute()
    {
        return trim(
            ($this->first_name ?? '') . ' ' .
                ($this->middle_name ?? '') . ' ' .
                ($this->last_name ?? '')
        );
    }
}
