<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class RegistrationCovidReportMaster extends Model {
    protected $table = 'registration_covid_report_masters';
    protected $fillable = ['username','covid_status','remarks'];
}
