<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FcRegistrationMaster extends Model
{
    protected $table = 'fc_registration_master1';
    public $primaryKey = 'pk';

    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'contact_no',
        'rank',
        'web_auth',
    ];

    public $timestamps = false; // or true if you're using created_date
}
