<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class LoginAttemptsLog extends Model {
    protected $table = 'login_attempts_logs';
    protected $fillable = ['username','ip_address','success','user_agent','attempted_at'];
    public $timestamps = true;
}
