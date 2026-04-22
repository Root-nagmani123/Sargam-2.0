<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class RegistrationBankDetailsMaster extends Model {
    protected $table = 'registration_bank_details_masters';
    protected $fillable = ['username','bank_name','ifsc_code','account_no','account_holder_name'];
}

