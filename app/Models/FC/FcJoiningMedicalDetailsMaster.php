<?php

namespace App\Models\FC;
use App\Models\FC\Concerns\FcUserAware;
use Illuminate\Database\Eloquent\Model;

class FcJoiningMedicalDetailsMaster extends Model {
    use FcUserAware;
    protected $table = 'fc_joining_medical_details_masters';
    protected $fillable = ['user_id', 'username','height_cm','weight_kg','blood_pressure','blood_group',
        'is_fit','medical_remarks','examined_by','examined_date'];
    protected $casts = ['is_fit'=>'boolean','examined_date'=>'date'];
}
