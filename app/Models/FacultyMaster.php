<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacultyMaster extends Model
{
    protected $table = "faculty_master";
    protected $guarded = [];
    public $timestamps = false;
    public $primaryKey = "pk";

    protected $casts = [
        'joining_date' => 'date'
    ];

    public function facultyQualificationMap()
    {
        return $this->hasMany(FacultyQualificationMap::class, 'faculty_master_pk', 'pk');
    }

    public function facultyExpertiseMap()
    {
        return $this->hasMany(FacultyExpertiseMap::class, 'faculty_master_pk', 'pk');
    }

    public function facultyExperienceMap()
    {
        return $this->hasMany(FacultyExperienceMap::class, 'faculty_master_pk', 'pk');
    }

    public function facultyTypeMaster()
    {
        return $this->belongsTo(FacultyTypeMaster::class, 'faculty_type', 'pk');
    }

    public function countryMaster() 
    {
        return $this->belongsTo(Country::class, 'country_master_pk', 'pk');
    }

    public function stateMaster() 
    {
        return $this->belongsTo(State::class, 'state_master_pk', 'Pk');
    }

    public function districtMaster() 
    {
        return $this->belongsTo(District::class, 'state_district_mapping_pk', 'pk');
    }

    public function cityMaster() 
    {
        return $this->belongsTo(City::class, 'city_master_pk', 'pk');
    }

    public function timetableCourses()
    {
        return $this->hasMany(CalendarEvent::class, 'faculty_master', 'pk');
    }

    public function mdoEscotDutyMaps()
    {
        return $this->hasMany(MDOEscotDutyMap::class, 'faculty_master_pk', 'pk');
    }
}
