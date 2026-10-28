<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** COE › Master Data › Subject Max Marks: one max-marks value per subject. */
class SubjectMaxMarksMaster extends Model
{
    protected $table = 'subject_max_marks_master';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    protected $fillable = ['subject_master_pk', 'max_marks', 'active_inactive', 'created_date', 'modified_date'];

    protected $casts = ['active_inactive' => 'integer'];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(SubjectMaster::class, 'subject_master_pk', 'pk');
    }
}
