<?php
namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class StudentMasterSecond extends Model
{
    protected $table = 'student_master_seconds';
    protected $fillable = [
        'username','category_id','religion_id','nationality','domicile_state',
        'marital_status','blood_group','height_cm','weight_kg',
        'identification_mark1','identification_mark2',
        'perm_address_line1','perm_address_line2','perm_city','perm_state_id','perm_pincode','perm_country_id',
        'pres_address_line1','pres_address_line2','pres_city','pres_state_id','pres_pincode','pres_country_id',
        'emergency_contact_name','emergency_contact_relation','emergency_contact_mobile',
        'father_profession_id','father_occupation_details','step2_completed',
    ];
    protected $casts = ['step2_completed'=>'boolean'];

    public function category()        { return $this->belongsTo(CategoryMaster::class,'category_id','pk'); }
    public function religion()        { return $this->belongsTo(ReligionMaster::class,'religion_id','pk'); }
    public function permState()       { return $this->belongsTo(StateMaster::class,'perm_state_id'); }
    public function presState()       { return $this->belongsTo(StateMaster::class,'pres_state_id'); }
    public function fatherProfession(){ return $this->belongsTo(FatherProfession::class,'father_profession_id'); }
}
