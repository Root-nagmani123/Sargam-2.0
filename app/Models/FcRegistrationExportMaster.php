<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FcRegistrationExportMaster extends Model
{
    protected $table = 'fc_registration_master'; // Reference your actual table

    public $timestamps = false; // Optional

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
        'web_auth'
    ];
}
