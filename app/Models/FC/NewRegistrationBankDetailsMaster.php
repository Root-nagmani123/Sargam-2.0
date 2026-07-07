<?php

namespace App\Models\FC;
use App\Models\FC\Concerns\FcUserAware;
use Illuminate\Database\Eloquent\Model;

class NewRegistrationBankDetailsMaster extends Model {
    use FcUserAware;
    protected $table = 'new_registration_bank_details_masters';
    protected $fillable = ['user_id', 'username','bank_name','branch_name','ifsc_code','account_no',
        'account_holder_name','account_type','bank_passbook_path','is_verified'];
    protected $casts = ['is_verified'=>'boolean'];
}
