<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FamilyIDCardRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'family_id_card_requests';

    protected $fillable = [
        'employee_id',
        'employee_name',
        'designation',
        'card_type',
        'name',
        'relation',
        'section',
        'group_photo',
        'family_photo',
        'dob',
        'valid_from',
        'valid_to',
        'family_member_id',
        'status',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'dob' => 'date',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
