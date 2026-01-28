<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Model;

class SubStore extends Model
{
    public const STATUS_ACTIVE   = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $table = 'mess_sub_stores';
    
    protected $fillable = [
        'parent_store_id',
        'sub_store_name',
        'status',
    ];

    /**
     * Get the parent store that owns this sub store
     */
    public function parentStore()
    {
        return $this->belongsTo(Store::class, 'parent_store_id');
    }

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
