<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Model;

class SubStore extends Model
{
    public const STATUS_ACTIVE   = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $table = 'mess_sub_stores';
    
    protected $fillable = [
        'sub_store_name',
        'status',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status ?: self::STATUS_ACTIVE;
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return $this->status === self::STATUS_ACTIVE ? 'success' : 'danger';
    }
}
