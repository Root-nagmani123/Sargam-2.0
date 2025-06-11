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

    public function scopeActive($query)
    {
        return $query->where('active_inactive', 1);
    }
}
