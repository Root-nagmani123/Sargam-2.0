<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $table = 'state_district_mapping';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    protected $fillable = [
        'state_master_pk',
        'country_master_pk',
        'district_name',
        'active_inactive',
    ];
    public function cities()
    {
        return $this->hasMany(City::class, 'district_master_pk', 'pk');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_master_pk', 'pk');
    }

    public function state()
    {
        return $this->belongsTo(State::class, 'state_master_pk', 'pk');
    }

    public static function getDistrictList()
    {
        return self::select('pk', 'district_name')->get()->pluck('district_name', 'pk');
    }
}
