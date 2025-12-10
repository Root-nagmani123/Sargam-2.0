<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'holiday_name',
        'holiday_date',
        'holiday_type',
        'description',
        'year',
        'active_inactive'
    ];

    protected $casts = [
        'holiday_date' => 'date',
        'active_inactive' => 'boolean',
    ];

    /**
     * Scope to get holidays by year
     */
    public function scopeByYear($query, $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope to get active holidays
     */
    public function scopeActive($query)
    {
        return $query->where('active_inactive', 1);
    }

    /**
     * Scope to get holidays by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('holiday_type', $type);
    }
}
