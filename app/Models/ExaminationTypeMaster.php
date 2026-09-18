<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExaminationTypeMaster extends Model
{
    protected $table = 'examination_type_master';
    protected $primaryKey = 'pk';
    protected $guarded = [];

    public $timestamps = false;

    public function scopeActive($query)
    {
        return $query->where('active_inactive', 1);
    }
}
