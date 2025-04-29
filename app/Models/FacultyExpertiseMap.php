<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacultyExpertiseMap extends Model
{
    protected $table = "faculty_expertise_map";
    protected $guarded = [];
    public $timestamps = false;
    public $primaryKey = "pk";

    public function facultyMaster()
    {
        return $this->belongsTo(FacultyMaster::class, 'faculty_master_pk', 'pk');
    }

    // public function facultyExpertise()
    // {
    //     return $this->belongsTo(FacultyExpertise::class, 'faculty_expertise_pk', 'pk');
    // }
}
