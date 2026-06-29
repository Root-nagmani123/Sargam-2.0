<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExemptionMaster extends Model
{
    protected $table = 'exemption_master';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    protected $fillable = [
        'course_master_pk',
        'effective_from',
        'gender',
        'exemption_days',
        'apply_cutoff_time',
        'active_inactive',
        'created_by',
        'created_date',
        'modified_date',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'exemption_days' => 'decimal:1',
    ];

    public function course()
    {
        return $this->belongsTo(CourseMaster::class, 'course_master_pk', 'pk');
    }
}
