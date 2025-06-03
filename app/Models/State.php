<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    protected $table = 'state_master';
    protected $primaryKey = 'Pk';
    public $timestamps = false;

    protected $fillable = [
        'Pk',
        'state_name',
        'country_master_pk',
        'created_by',
        'created_date',
        'modified_by',
        'modified_date',
    ];

    public static function getStateList()
    {
        return self::select('Pk', 'state_name')->get()->pluck('state_name', 'Pk');
    }
    public function cities()
    {
        return $this->hasMany(City::class, 'state_master_pk', 'pk');
    }
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_master_pk', 'pk');
    }
}
