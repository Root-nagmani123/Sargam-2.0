<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $table = 'city_master';
    protected $primaryKey = 'pk';
    public $timestamps = true;
    
    protected $fillable = [
        'country_master_pk',
        'state_master_pk',
        'district_master_pk',
        'city_name',
        'active_inactive',
    ];

    public static function getCityList()
    {
        return self::select('pk', 'city_name')->get()->pluck('city_name', 'pk');
    }
    public function state()
    {
        return $this->belongsTo(State::class, 'state_master_pk', 'pk');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_master_pk', 'pk');
    }
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_master_pk', 'pk');
    }
}
