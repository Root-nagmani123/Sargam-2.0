<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FcRegistrationExportMaster extends Model
{
    protected $table = 'fc_registration_master1'; // Reference your actual table

    public $timestamps = false; // Optional

    protected $fillable = [
        'first_name', 'middle_name', 'last_name', 'email', 'contact_no', 'rank', 'web_auth'
    ];
}
