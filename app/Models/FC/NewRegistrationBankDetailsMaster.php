<?php

namespace App\Models\FC;
use Illuminate\Database\Eloquent\Model;

class NewRegistrationBankDetailsMaster extends Model {
    protected $table = 'new_registration_bank_details_masters';
    protected $fillable = ['username','bank_name','branch_name','ifsc_code','account_no',
        'account_holder_name','account_type','bank_passbook_path','is_verified'];
    protected $casts = ['is_verified'=>'boolean'];
}
