<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSessionMaster extends Model
{
    protected $table = 'class_session_master';
    protected $primaryKey = 'pk';
    protected $guarded = [];

    public $timestamps = false;

}
