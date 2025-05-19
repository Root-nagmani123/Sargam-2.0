<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MDODutyTypeMaster extends Model
{
    protected $table = "mdo_duty_type_master";
    protected $guarded = [];

    public $timestamps = false;

    protected $primaryKey = 'pk';
}
