<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DashboardStatSnapshot extends Model
{
    use HasFactory;

    protected $table = 'dashboard_stat_snapshots';

    protected $fillable = ['snapshot_date', 'title', 'is_default'];

    protected $casts = [
        'snapshot_date' => 'date',
        'is_default' => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(DashboardStatItem::class)->orderBy('sort_order');
    }

    public static function getDefaultOrLatest()
    {
        $snapshot = static::where('is_default', true)->first();
        if (!$snapshot) {
            $snapshot = static::orderBy('snapshot_date', 'desc')->first();
        }
        return $snapshot;
    }
}
