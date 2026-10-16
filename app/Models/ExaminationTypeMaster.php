<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ExaminationTypeMaster extends Model
{
    protected $table = 'examination_type_master';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    protected $fillable = ['exam_type_name', 'active_inactive', 'created_date', 'modified_date'];

    protected $casts = [
        'active_inactive' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active_inactive', 1);
    }
}
