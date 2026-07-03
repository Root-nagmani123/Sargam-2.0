<?php

namespace App\Models\FC;
use App\Models\FC\Concerns\FcUserAware;
use Illuminate\Database\Eloquent\Model;

class StudentConfirmMaster extends Model {
    use FcUserAware;
    protected $table = 'student_confirm_masters';
    protected $fillable = ['user_id', 'username','declaration_accepted','confirmed_at','ip_address'];
    protected $casts = ['declaration_accepted'=>'boolean','confirmed_at'=>'datetime'];
}
