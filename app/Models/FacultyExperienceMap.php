<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacultyExperienceMap extends Model
{
    protected $table = "faculty_experience_map";
    protected $guarded = [];
    public $timestamps = false;
    public $primaryKey = "pk";

    public function facultyMaster()
    {
        return $this->belongsTo(FacultyMaster::class, 'faculty_master_pk', 'pk');
    }
}
