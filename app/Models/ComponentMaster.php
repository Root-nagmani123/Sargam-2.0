<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComponentMaster extends Model
{
    protected $table = 'component_master';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    protected $fillable = ['component_name', 'active_inactive', 'created_date', 'modified_date'];

    protected $casts = ['active_inactive' => 'integer'];
}
