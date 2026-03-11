<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleCounter extends Model
{
    use HasFactory;
    
    protected $table = 'mess_sale_counters';
    
    protected $fillable = [
        'counter_name',
        'counter_code',
        'store_id',
        'location',
        'is_active'
    ];
    
    protected $casts = [
        'is_active' => 'boolean'
    ];
    
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
    
    public function mappings()
    {
        return $this->hasMany(SaleCounterMapping::class);
    }
    
    public function transactions()
    {
        return $this->hasMany(SaleCounterTransaction::class);
    }
}
