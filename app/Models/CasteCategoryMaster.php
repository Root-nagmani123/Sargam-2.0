<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CasteCategoryMaster extends Model
{
    protected $table = 'caste_category_master';
    protected $primaryKey = 'pk';
    protected $guarded = [];
    public $timestamps = false;

    public function scopeActive($query)
    {
        return $query->where('active_inactive', 1);
    }

    public static function GetSeatName()
    {
        return self::active()->select('pk', 'Seat_name', 'Seat_name_hindi')
            ->get()
            ->map(function ($item) {
                $item->seat_name = $item->Seat_name . ($item->Seat_name_hindi ? ' (' . $item->Seat_name_hindi . ')' : '');
                return $item;
            })
            ->pluck('seat_name', 'pk');
    }
}
