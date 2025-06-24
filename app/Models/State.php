<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    protected $table = 'state_master';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    protected $fillable = [
        'pk',
        'state_name',
        'country_master_pk',
        'active_inactive',
        'created_by',
        'created_date',
        'modified_by',
        'modified_date',
    ];

    public static function getStateList()
    {
        return self::select('pk', 'state_name')->get()->pluck('state_name', 'pk');
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
