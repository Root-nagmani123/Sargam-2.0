<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorItemMapping extends Model
{
    use HasFactory;

    public const MAPPING_TYPE_ITEM_CATEGORY = 'item_category';
    public const MAPPING_TYPE_ITEM_SUB_CATEGORY = 'item_sub_category';

    protected $table = 'mess_vendor_item_mappings';

    protected $fillable = [
        'vendor_id',
        'mapping_type',
        'item_category_id',
        'item_subcategory_id',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function itemCategory()
    {
        return $this->belongsTo(ItemCategory::class, 'item_category_id');
    }

    public function itemSubcategory()
    {
        return $this->belongsTo(ItemSubcategory::class, 'item_subcategory_id');
    }
}
