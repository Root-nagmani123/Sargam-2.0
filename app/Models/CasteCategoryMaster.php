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

    /**
     * `category_name` <-> `Seat_name`.
     *
     * The controller, the form field, the index cell and the AJAX payload were all
     * written against a `category_name` column that does not exist on
     * caste_category_master — the real column is `Seat_name`. The read side showed
     * a blank name on every row; the write side could not save at all.
     *
     * Mapping it here rather than renaming everything keeps the form field, the
     * validation key and the JSON keys the JS reads unchanged, and keeps the real
     * column name authoritative in one place.
     *
     * NOTE: Rule::unique() queries the table directly and does NOT pass through
     * this accessor, so validation rules must name `Seat_name` explicitly.
     */
    public function getCategoryNameAttribute(): ?string
    {
        return $this->attributes['Seat_name'] ?? null;
    }

    public function setCategoryNameAttribute($value): void
    {
        $this->attributes['Seat_name'] = $value;
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
