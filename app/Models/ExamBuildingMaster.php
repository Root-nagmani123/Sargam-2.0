<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamBuildingMaster extends Model
{
    protected $table = 'exam_building_master';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    protected $fillable = [
        'building_name',
        'building_short_name',
        'description',
        'active_inactive',
        'created_date',
        'modified_date',
    ];

    public function scopeActive($query)
    {
        return $query->where('active_inactive', 1);
    }
}
