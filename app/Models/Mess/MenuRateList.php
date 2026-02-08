<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuRateList extends Model
{
    use HasFactory;
    
    protected $table = 'mess_menu_rate_lists';
    
    protected $fillable = [
        'menu_item_name',
        'inventory_id',
        'rate',
        'effective_from',
        'effective_to',
        'is_active'
    ];
    
    protected $casts = [
        'rate' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean'
    ];
    
    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }
}
