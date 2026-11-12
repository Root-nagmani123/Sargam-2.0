<?php

namespace App\Models\COE;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** COE › Master Data › Grade Master: one grade and the percentage range it covers. */
class GradeMaster extends Model
{
    public const RESULT_STATUSES = ['Pass', 'Not Qualified'];

    protected $table = 'coe_grade_master';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    protected $fillable = ['grade', 'classification', 'min_percent', 'max_percent', 'result_status', 'active_inactive', 'created_date', 'modified_date'];

    protected $casts = ['active_inactive' => 'integer'];

    /** Active grades whose range shares at least one value with min..max (both ends inclusive). */
    public static function activeOverlapping($min, $max, $exceptPk = null): Builder
    {
        return static::query()
            ->where('active_inactive', 1)
            ->where('min_percent', '<=', $max)
            ->where('max_percent', '>=', $min)
            ->when($exceptPk, fn ($q) => $q->whereKeyNot($exceptPk));
    }
}
