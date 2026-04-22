<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class StudentMasterImmovablePropertyDetails extends Model {
    protected $table = 'student_master_immovable_property_details';
    protected $fillable = ['username','property_type','location','area_sq_ft','value_in_lakhs','how_acquired'];
}

