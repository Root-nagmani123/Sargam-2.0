<?php

namespace App\Models\Athletics;

use App\Models\StudentMaster;
use Illuminate\Database\Eloquent\Model;

class AthleticsResult extends Model
{
    protected $table = 'athletics_results';

    protected $fillable = [
        'drive_id', 'event_id', 'position', 'student_master_pk', 'house_id',
        'performance', 'points', 'remarks', 'created_by',
    ];

    public function student()
    {
        return $this->belongsTo(StudentMaster::class, 'student_master_pk', 'pk');
    }

    public function house()
    {
        return $this->belongsTo(AthleticsHouse::class, 'house_id');
    }
}
