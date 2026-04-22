<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class StateMaster extends Model {
    protected $table = 'state_master';
    protected $primaryKey = 'pk';
    protected $fillable = ['state_name','state_code'];
    public function districts() { return $this->hasMany(StateDistrictMaster::class,'state_id'); }
}
