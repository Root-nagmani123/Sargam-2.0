<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class SessionMaster extends Model {
    protected $table = 'session_masters';
    protected $fillable = ['session_name','session_code','start_date','end_date','is_active'];
}
