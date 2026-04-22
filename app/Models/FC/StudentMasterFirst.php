<?php
namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class StudentMasterFirst extends Model
{
    protected $table = 'student_master_firsts';
    protected $fillable = [
        'username','roll_no','session_id','full_name','fathers_name','mothers_name',
        'date_of_birth','gender','service_id','cadre','allotted_state_id',
        'mobile_no','email','photo_path','signature_path','step1_completed',
    ];
    protected $casts = ['date_of_birth'=>'date','step1_completed'=>'boolean'];

    public function session()     { return $this->belongsTo(SessionMaster::class,'session_id'); }
    public function service()     { return $this->belongsTo(ServiceMaster::class,'service_id','pk'); }
    public function allottedState(){ return $this->belongsTo(StateMaster::class,'allotted_state_id','pk'); }
    public function user()        { return $this->belongsTo(JbpUser::class,'username','username'); }
}
