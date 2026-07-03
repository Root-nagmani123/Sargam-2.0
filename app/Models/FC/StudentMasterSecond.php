<?php

namespace App\Models\FC;

use App\Models\FC\Concerns\FcUserAware;
use Illuminate\Database\Eloquent\Model;

class StudentMasterSecond extends Model
{
    use FcUserAware;

    protected $table = 'student_master_seconds';

    protected $fillable = [
        'user_id',
        'category_id', 'religion_id', 'nationality', 'domicile_state', 'domicile_state_id', 'domicile_district',
        'marital_status', 'blood_group', 'height_cm', 'weight_kg',
        'identification_mark1', 'identification_mark2',
        'birth_state_id', 'birth_district', 'birth_city', 'birth_area_type',
        'perm_address_line1', 'perm_address_line2', 'perm_city',
        'perm_state_id', 'perm_pincode', 'perm_country_id',
        'perm_district', 'perm_city_name',
        'pres_address_line1', 'pres_address_line2', 'pres_city',
        'pres_state_id', 'pres_pincode', 'pres_country_id',
        'pres_district', 'pres_city_name',
        'emergency_contact_name', 'emergency_contact_relation', 'emergency_contact_mobile',
        'mother_first_name', 'mother_middle_name', 'mother_last_name',
        'mother_qualification_id', 'mother_profession_id', 'mother_annual_income',
        'father_first_name', 'father_middle_name', 'father_last_name',
        'father_qualification_id', 'father_profession_id', 'father_annual_income',
        'father_occupation_details',
        'guardian_or_spouse', 'guardian_first_name', 'guardian_middle_name', 'guardian_last_name',
        'guardian_contact_no', 'guardian_email',
        'dietary_preference', 'high_altitude_condition', 'high_altitude_remarks',
        'highest_stream_id', 'matric_state_id', 'matric_district', 'matric_city', 'matric_city_name',
        'cse_attempts', 'previous_service_id',
        'health_asthma', 'health_lung_disease', 'health_kidney_disease', 'health_diabetes',
        'health_blood_disorder', 'health_immunocompromised', 'health_liver_disease',
        'health_cardiac_condition', 'health_pregnant_lactating', 'health_additional_info',
        'health_completed',
        'step2_completed',
    ];

    protected $casts = [
        'step2_completed'  => 'boolean',
        'health_completed' => 'boolean',
    ];

    public function category()         { return $this->belongsTo(CategoryMaster::class, 'category_id', 'pk'); }
    public function religion()         { return $this->belongsTo(ReligionMaster::class, 'religion_id', 'pk'); }
    public function permState()        { return $this->belongsTo(StateMaster::class, 'perm_state_id'); }
    public function presState()        { return $this->belongsTo(StateMaster::class, 'pres_state_id'); }
    public function fatherProfession() { return $this->belongsTo(FatherProfession::class, 'father_profession_id'); }
}
