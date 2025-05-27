<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExemptionCategoryMaster extends Model
{
    protected $table = 'exemption_category_master';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    protected $fillable = [
        'exemp_category_name',
        'exemp_cat_short_name',
        'active_inactive',
        'created_date',
        'modified_date'
    ];

}
