<?php

namespace App\Models\FC;

use Illuminate\Database\Eloquent\Model;
use App\Models\FC\Concerns\FcUserAware;

class FcPreHistory extends Model
{
    use FcUserAware;
    protected $table = 'fc_pre_history';

    protected $fillable = [
        'user_id',  // post-migration column name
        'userid',   // pre-migration column name (fc_pre_history uses 'userid')
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
