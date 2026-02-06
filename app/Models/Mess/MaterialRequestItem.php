<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Model;

class MaterialRequestItem extends Model
{
    protected $table = 'mess_material_request_items';
    
    protected $fillable = [
        'material_request_id', 'inventory_id', 'requested_quantity',
        'approved_quantity', 'unit', 'remarks'
    ];
    
    public function materialRequest()
    {
        return $this->belongsTo(MaterialRequest::class, 'material_request_id');
    }
    
    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }
}
