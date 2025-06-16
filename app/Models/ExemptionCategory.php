<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExemptionCategory extends Model
{
    use HasFactory;

    protected $table = 'fc_exemption_categories_data'; // your table name
    protected $primaryKey = 'pk';

    protected $fillable = [
        'cse_heading',
        'cse_subheading',
        'attended_heading',
        'attended_subheading',
        'medical_heading',
        'medical_subheading',
        'optout_heading',
        'optout_subheading',
        'important_notice',
    ];
}


