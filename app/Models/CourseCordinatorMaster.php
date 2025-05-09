<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseCordinatorMaster extends Model
{
    protected $table = 'course_coordinator_master';
    protected $guarded = [];
    protected $primaryKey = 'pk';
    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'Modified_date';

    public function course()
    {
        return $this->belongsTo(CoursesMaster::class, 'courses_master_pk', 'pk');
    }
}
