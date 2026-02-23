<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ItemSubcategory extends Model
{
    public const STATUS_ACTIVE   = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $table = 'mess_item_subcategories';
    
    protected $fillable = [
        'category_id',
        'name', // Original column name (may exist)
        'item_name', // Renamed column (after migration)
        'subcategory_name', // Alternative name (may exist)
        'item_code', // Renamed column (after migration)
        'subcategory_code', // Alternative name (may exist)
        'unit_measurement',
        'standard_cost',
        'alert_quantity',
        'description',
        'status',
        'is_active', // Old status column
    ];

    /**
     * Get the category that owns the subcategory
     */
    public function category()
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    /**
     * Accessor for item_name - handles name, item_name, and subcategory_name
     */
    public function getItemNameAttribute()
    {
        if (isset($this->attributes['item_name'])) {
            return $this->attributes['item_name'];
        }
        if (isset($this->attributes['subcategory_name'])) {
            return $this->attributes['subcategory_name'];
        }
        if (isset($this->attributes['name'])) {
            return $this->attributes['name'];
        }
        return null;
    }

    /**
     * Mutator for item_name - saves to the correct column
     */
    public function setItemNameAttribute($value)
    {
        if (Schema::hasColumn('mess_item_subcategories', 'item_name')) {
            $this->attributes['item_name'] = $value;
        } elseif (Schema::hasColumn('mess_item_subcategories', 'subcategory_name')) {
            $this->attributes['subcategory_name'] = $value;
        } elseif (Schema::hasColumn('mess_item_subcategories', 'name')) {
            $this->attributes['name'] = $value;
        }
    }

    /**
     * Accessor for item_code - handles both item_code and subcategory_code
     */
    public function getItemCodeAttribute()
    {
        if (isset($this->attributes['item_code'])) {
            return $this->attributes['item_code'];
        }
        if (isset($this->attributes['subcategory_code'])) {
            return $this->attributes['subcategory_code'];
        }
        return null;
    }

    /**
     * Mutator for item_code - saves to the correct column
     */
    public function setItemCodeAttribute($value)
    {
        if (Schema::hasColumn('mess_item_subcategories', 'item_code')) {
            $this->attributes['item_code'] = $value;
        } elseif (Schema::hasColumn('mess_item_subcategories', 'subcategory_code')) {
            $this->attributes['subcategory_code'] = $value;
        }
    }

    /**
     * Accessor for status - handles both status and is_active columns
     */
    public function getStatusAttribute()
    {
        if (isset($this->attributes['status'])) {
            return $this->attributes['status'];
        }
        // Fallback to is_active if status column doesn't exist
        if (isset($this->attributes['is_active'])) {
            return $this->attributes['is_active'] ? self::STATUS_ACTIVE : self::STATUS_INACTIVE;
        }
        return self::STATUS_ACTIVE;
    }

    public function scopeActive($query)
    {
        if (Schema::hasColumn('mess_item_subcategories', 'status')) {
            return $query->where('status', self::STATUS_ACTIVE);
        }
        // Fallback to is_active
        if (Schema::hasColumn('mess_item_subcategories', 'is_active')) {
            return $query->where('is_active', true);
        }
        return $query;
    }

    public function getStatusLabelAttribute(): string
    {
        $status = $this->status;
        return $status ?: self::STATUS_ACTIVE;
    }

    public function getStatusBadgeClassAttribute(): string
    {
        $status = $this->status;
        return $status === self::STATUS_ACTIVE ? 'success' : 'danger';
    }
}
