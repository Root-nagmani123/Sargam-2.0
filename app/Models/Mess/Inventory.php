<?php
namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;
    
    protected $table = 'mess_inventories';
    
    protected $fillable = [
        'item_name', 
        'category_id', 
        'subcategory_id',
        'store_id',
        'item_code',
        'quantity', 
        'current_stock',
        'minimum_stock',
        'unit', 
        'unit_price',
        'expiry_date'
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'quantity' => 'decimal:2',
        'current_stock' => 'decimal:2',
        'minimum_stock' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    /**
     * Get the category
     */
    public function category()
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    /**
     * Get the subcategory
     */
    public function subcategory()
    {
        return $this->belongsTo(ItemSubcategory::class, 'subcategory_id');
    }

    /**
     * Get the store
     */
    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * Get purchase order items
     */
    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class, 'inventory_id');
    }
}
