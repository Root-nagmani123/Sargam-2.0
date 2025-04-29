<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacultyMaster extends Model
{
    protected $table = "faculty_master";
    protected $guarded = [];
    public $timestamps = false;
    public $primaryKey = "pk";

    public function facultyQualificationMap()
    {
        return $this->hasMany(FacultyQualificationMap::class, 'faculty_master_pk', 'pk');
    }
}
