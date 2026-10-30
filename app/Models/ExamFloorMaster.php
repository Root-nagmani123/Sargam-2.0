<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamFloorMaster extends Model
{
    protected $table = 'exam_floor_master';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    protected $fillable = [
        'floor_name',
        'display_order',
        'active_inactive',
        'created_date',
        'modified_date',
    ];

    public function scopeActive($query)
    {
        return $query->where('active_inactive', 1);
    }
}
