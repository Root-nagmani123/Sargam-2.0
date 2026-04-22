<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class StudentMasterMovablePropertyDetails extends Model {
    protected $table = 'student_master_movable_property_details';
    protected $fillable = ['username','property_type','description','value_in_lakhs','location'];
}
