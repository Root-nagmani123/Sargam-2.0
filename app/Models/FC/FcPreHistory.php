<?php

namespace App\Models\FC;

use Illuminate\Database\Eloquent\Model;

class FcPreHistory extends Model
{
    protected $table = 'fc_pre_history';

    protected $fillable = [
        'userid',
        'allergy_illness',
        'prolonged_medication',
        'hospital_history',
        'altitude_illness',
        'additional_info',
        'doc_path',
        'course',
        'status',
    ];
}
