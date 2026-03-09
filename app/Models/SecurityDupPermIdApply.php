<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityDupPermIdApply extends Model
{
    protected $table = 'security_dup_perm_id_apply';

    protected $primaryKey = 'emp_id_apply';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'card_valid_from' => 'date',
        'card_valid_to' => 'date',
        'employee_dob' => 'date',
        'created_date' => 'datetime',
        'id_card_generate_date' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(EmployeeMaster::class, 'employee_master_pk', 'pk');
    }
}

