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
        'max_exemption_per_month',
        'description',
        'apply_cutoff_time',
        'freeze_before_minutes',
        'active_inactive',
        'created_by',
        'created_date',
        'modified_date',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'exemption_days' => 'decimal:1',
        'max_exemption_per_month' => 'decimal:1',
        'freeze_before_minutes' => 'integer',
    ];

    public function course()
    {
        return $this->belongsTo(CourseMaster::class, 'course_master_pk', 'pk');
    }
}
