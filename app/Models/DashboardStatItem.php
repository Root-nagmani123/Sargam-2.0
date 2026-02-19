<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DashboardStatItem extends Model
{
    use HasFactory;

    protected $table = 'dashboard_stat_items';

    protected $fillable = [
        'dashboard_stat_snapshot_id',
        'chart_type',
        'label',
        'female_count',
        'male_count',
        'value',
        'sort_order',
    ];

    protected $casts = [
        'female_count' => 'integer',
        'male_count' => 'integer',
        'value' => 'float',
        'sort_order' => 'integer',
    ];

    public function snapshot()
    {
        return $this->belongsTo(DashboardStatSnapshot::class, 'dashboard_stat_snapshot_id');
    }
}
