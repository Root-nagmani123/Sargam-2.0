<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** COE › Master Data › Service Passing Marks: one passing-marks value per service. */
class ServicePassingMarksMaster extends Model
{
    protected $table = 'service_passing_marks_master';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    protected $fillable = ['service_master_pk', 'passing_marks', 'active_inactive', 'created_date', 'modified_date'];

    protected $casts = ['active_inactive' => 'integer'];

    public function service(): BelongsTo
    {
        return $this->belongsTo(ServiceMaster::class, 'service_master_pk', 'pk');
    }
}
