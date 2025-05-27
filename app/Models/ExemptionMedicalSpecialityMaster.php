<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExemptionMedicalSpecialityMaster extends Model
{
   protected $table = 'exemption_medical_speciality_master';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    protected $fillable = [
        'speciality_name',
        'active_inactive',
    ];
}
