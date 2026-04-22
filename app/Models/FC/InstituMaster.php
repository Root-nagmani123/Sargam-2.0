<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class InstituMaster extends Model {
    protected $table = 'institu_masters';
    protected $fillable = ['institute_name','institute_code'];
}
