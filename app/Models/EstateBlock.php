<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstateBlock extends Model
{
    protected $table = 'estate_block_master';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    protected $fillable = ['block_name'];
}
