<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class CategoryMaster extends Model {
    protected $table = 'caste_category_master';
    protected $primaryKey = 'pk';
    protected $fillable = ['Seat_name','Seat_name_hindi'];
}
