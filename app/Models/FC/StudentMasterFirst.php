<?php

namespace App\Models\FC;

use App\Models\FC\Concerns\FcUserAware;
use Illuminate\Database\Eloquent\Model;

class StudentMasterFirst extends Model
{
    use FcUserAware;

    protected $table = 'student_master_firsts';

    protected $fillable = [
        'user_id',
        'roll_no', 'session_id',
        'full_name', 'fathers_name', 'mothers_name',
        'full_name_hindi', 'first_name', 'middle_name', 'last_name',
        'date_of_birth', 'gender', 'background',
        'service_id', 'cadre', 'allotted_state_id',
        'mobile_no', 'alt_mobile_no', 'email', 'alt_email',
        'instagram_id', 'twitter_id',
        'pan_card', 'aadhar_number', 'passport_no',
        'photo_path', 'signature_path',
        'vision_statement', 'vision_completed',
        'step1_completed',
    ];

    protected $casts = [
        'date_of_birth'    => 'date',
        'step1_completed'  => 'boolean',
        'vision_completed' => 'boolean',
    ];

    public function session()      { return $this->belongsTo(SessionMaster::class, 'session_id'); }
    public function service()      { return $this->belongsTo(ServiceMaster::class, 'service_id', 'pk'); }
    public function allottedState(){ return $this->belongsTo(StateMaster::class, 'allotted_state_id', 'pk'); }
    public function user()         { return $this->belongsTo(\App\Models\User::class, 'user_id', 'pk'); }
}
