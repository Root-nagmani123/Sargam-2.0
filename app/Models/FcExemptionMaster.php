<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FcExemptionMaster extends Model
{
    protected $table = 'fc_exemption_master';
    protected $primaryKey = 'Pk';
    public $timestamps = false;

    protected $fillable = [
        'Exemption_name',
        'Exemption_short_name',
        'Created_by',
        'Created_date',
        'Modified_by',
        'Modified_date',
    ];
}