<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FcRegistrationMaster extends Model
{
    protected $table = 'fc_registration_master';
    public $primaryKey = 'pk';

    protected $fillable = [
        'email',
        'contact_no',
        'display_name',
        'schema_id',
        'first_name',
        'middle_name',
        'last_name',
        'rank',
        'exam_year',
        'service_master_pk',
        'web_auth',
    ];


    public $timestamps = false; // or true if you're using created_date
}
