<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstateElectricSlab extends Model
{
    protected $table = 'estate_electric_slab';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    protected $fillable = ['start_unit_range', 'end_unit_range', 'rate_per_unit', 'house'];

    protected $casts = [
        'start_unit_range' => 'integer',
        'end_unit_range' => 'integer',
        'rate_per_unit' => 'decimal:2',
        'house' => 'integer',
    ];
}
