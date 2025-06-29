<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $table = 'country_master';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    protected $fillable = [
        'country_name',
        'created_by',
        'active_inactive',
        'created_date',
        'updated_by',
        'updated_date',
    ];

    public static function getCountryList()
    {
        return self::select('pk', 'country_name')->get()->pluck('country_name', 'pk');
    }
}
