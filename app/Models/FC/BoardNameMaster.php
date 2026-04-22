<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class BoardNameMaster extends Model {
    protected $table = 'university_board_name_master';
    protected $primaryKey = 'pk';
    protected $fillable = ['board_name'];
}
