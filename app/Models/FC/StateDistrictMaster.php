<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class StateDistrictMaster extends Model {
    protected $table = 'state_district_masters';
    protected $fillable = ['state_id','district_name'];
    public function state() { return $this->belongsTo(StateMaster::class,'state_id'); }
}
