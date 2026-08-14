<?php

namespace App\Models\Mess;

use App\Models\Mess\Concerns\HasActiveStatus;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasActiveStatus;

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

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'store_id');
    }
}
