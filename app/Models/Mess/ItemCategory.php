<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ItemCategory extends Model
{
    public const TYPE_RAW_MATERIAL = 'raw_material';
    public const TYPE_FINISHED_GOOD = 'finished_good';
    public const TYPE_CONSUMABLE = 'consumable';
    public const TYPE_EQUIPMENT = 'equipment';

    public const STATUS_ACTIVE   = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $table = 'mess_item_categories';
    
    protected $fillable = [
        'category_name',
        'category_type',
        'description',
        'status', // Will be available after migration
    ];

    /**
     * @return array<string,string>
     */
    public static function categoryTypes(): array
    {
        return [
            self::TYPE_RAW_MATERIAL  => 'Raw Material',
            self::TYPE_FINISHED_GOOD => 'Finished Good',
            self::TYPE_CONSUMABLE    => 'Consumable',
            self::TYPE_EQUIPMENT     => 'Equipment',
        ];
    }

    /**
     * Accessor for category_type - handles missing column
     */
    public function getCategoryTypeAttribute()
    {
        return $this->attributes['category_type'] ?? self::TYPE_RAW_MATERIAL;
    }

    /**
     * Accessor for status - handles missing column
     */
    public function getStatusAttribute()
    {
        return $this->attributes['status'] ?? self::STATUS_ACTIVE;
    }

    public function scopeActive($query)
    {
        if (Schema::hasColumn('mess_item_categories', 'status')) {
            return $query->where('status', self::STATUS_ACTIVE);
        }
        // If status column doesn't exist, return all (no filtering)
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
