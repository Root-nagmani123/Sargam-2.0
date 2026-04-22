<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class PickUpDropTypeMaster extends Model {
    protected $table = 'pick_up_drop_type_masters';
    protected $fillable = ['type_name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}

