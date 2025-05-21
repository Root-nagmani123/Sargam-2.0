<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacultyTypeMaster extends Model
{
    protected $table = "faculty_type_master";
    protected $guarded = [];
    public $timestamps = false;
    public $primaryKey = "pk";

    public function facultyMaster()
    {
        return $this->hasMany(FacultyMaster::class, 'faculty_type_master_pk', 'pk');
    }
}
