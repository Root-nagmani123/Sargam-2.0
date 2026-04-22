<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class StudentConfirmMaster extends Model {
    protected $table = 'student_confirm_masters';
    protected $fillable = ['username','declaration_accepted','confirmed_at','ip_address'];
    protected $casts = ['declaration_accepted'=>'boolean','confirmed_at'=>'datetime'];
}
