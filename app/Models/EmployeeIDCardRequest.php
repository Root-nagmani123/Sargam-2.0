<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeIDCardRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_type',
        'card_type',
        'sub_type',
        'request_for',
        'name',
        'designation',
        'date_of_birth',
        'father_name',
        'academy_joining',
        'id_card_valid_upto',
        'mobile_number',
        'telephone_number',
        'blood_group',
        'section',
        'approval_authority',
        'vendor_organization_name',
        'photo',
        'documents',
        'status',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'academy_joining' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}

