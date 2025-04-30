<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacultyMaster extends Model
{
    protected $table = "faculty_master";
    protected $guarded = [];
    public $timestamps = false;
    public $primaryKey = "pk";

    protected $casts = [
        'joining_date' => 'date'
    ];

    public function facultyQualificationMap()
    {
        return $this->hasMany(FacultyQualificationMap::class, 'faculty_master_pk', 'pk');
    }

    public function facultyExpertiseMap()
    {
        return $this->hasMany(FacultyExpertiseMap::class, 'faculty_master_pk', 'pk');
    }

    public function facultyExperienceMap()
    {
        return $this->hasMany(FacultyExperienceMap::class, 'faculty_master_pk', 'pk');
    }

}
