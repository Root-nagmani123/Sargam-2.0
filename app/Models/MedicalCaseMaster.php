<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalCaseMaster extends Model
{
    protected $table = 'medical_case_master';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    protected $fillable = [
        'case_name',
        'active_inactive',
        'created_date',
        'modified_date',
    ];
}
