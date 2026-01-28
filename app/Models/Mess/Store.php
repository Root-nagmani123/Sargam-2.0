<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    public const TYPE_MESS    = 'mess';
    public const TYPE_CANTEEN = 'canteen';
    public const TYPE_GODOWN  = 'godown';

    public const STATUS_ACTIVE   = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $table = 'mess_stores';
    
    protected $fillable = [
        'store_name',
        'store_code',
        'location',
        'incharge_user_id',
        'status',
    ];

    /**
     * @return array<string,string>
     */
    public static function storeTypes(): array
    {
        return [
            self::TYPE_MESS    => 'Mess',
            self::TYPE_CANTEEN => 'Canteen',
            self::TYPE_GODOWN  => 'Godown',
        ];
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

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'store_id');
    }
    
    public function materialRequests()
    {
        return $this->hasMany(MaterialRequest::class, 'store_id');
    }
    
    public function inboundTransactions()
    {
        return $this->hasMany(InboundTransaction::class, 'store_id');
    }
}
