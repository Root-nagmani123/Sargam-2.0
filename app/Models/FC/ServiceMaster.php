<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class ServiceMaster extends Model {
    protected $table = 'service_master';
    protected $primaryKey = 'pk';
    protected $fillable = ['service_name','service_code'];
}
