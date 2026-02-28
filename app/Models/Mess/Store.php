<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    public const TYPE_MESS = 'mess';

    public const STATUS_ACTIVE   = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $table = 'mess_stores';

    protected $attributes = [
        'store_type' => 'mess',
    ];

    protected $fillable = [
        'store_name',
        'store_code',
        'store_type',
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
            self::TYPE_MESS => 'MESS',
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
