<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstateElectricSlab extends Model
{
    use HasFactory;

    protected $table = 'estate_electric_slab';
    protected $primaryKey = 'pk';

    protected $fillable = [
        'slab_name',
        'from_unit',
        'to_unit',
        'rate_per_unit',
        'description',
        'is_active',
        'created_by',
        'created_date',
        'modify_by',
        'modify_date',
    ];

    protected $casts = [
        'from_unit' => 'integer',
        'to_unit' => 'integer',
        'rate_per_unit' => 'decimal:2',
        'is_active' => 'boolean',
        'created_date' => 'datetime',
        'modify_date' => 'datetime',
    ];

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Calculate electricity charges based on units consumed
     */
    public static function calculateCharges($unitsConsumed)
    {
        $slabs = self::active()->orderBy('from_unit')->get();
        $totalCharge = 0;
        $remainingUnits = $unitsConsumed;

        foreach ($slabs as $slab) {
            if ($remainingUnits <= 0) break;

            $slabRange = $slab->to_unit - $slab->from_unit + 1;
            $unitsInThisSlab = min($remainingUnits, $slabRange);
            
            $totalCharge += $unitsInThisSlab * $slab->rate_per_unit;
            $remainingUnits -= $unitsInThisSlab;
        }

        return $totalCharge;
    }
}
