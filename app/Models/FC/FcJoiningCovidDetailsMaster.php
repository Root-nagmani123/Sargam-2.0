<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class FcJoiningCovidDetailsMaster extends Model {
    protected $table = 'fc_joining_covid_details_masters';
    protected $fillable = ['username','is_vaccinated','vaccine_name','dose_no','vaccination_date',
        'certificate_path','rtpcr_negative','rtpcr_date','rtpcr_doc_path'];
    protected $casts = ['is_vaccinated'=>'boolean','rtpcr_negative'=>'boolean',
        'vaccination_date'=>'date','rtpcr_date'=>'date'];
}

