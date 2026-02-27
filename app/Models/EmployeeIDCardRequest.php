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
        'duplication_reason',
        'name',
        'designation',
        'date_of_birth',
        'father_name',
        'academy_joining',
        'id_card_valid_upto',
        'id_card_valid_from',
        'id_card_number',
        'mobile_number',
        'telephone_number',
        'blood_group',
        'section',
        'approval_authority',
        'vendor_organization_name',
        'photo',
        'joining_letter',
        'fir_receipt',
        'payment_receipt',
        'documents',
        'status',
        'remarks',
        'rejection_reason',
        'created_by',
        'updated_by',
        'approved_by_a1',
        'approved_by_a2',
        'rejected_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'academy_joining' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'approved_by_a1_at' => 'datetime',
        'approved_by_a2_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /**
     * Request is awaiting Approver 1 (initial submission).
     */
    public function scopeAwaitingApprover1($query)
    {
        return $query->whereNull('approved_by_a1')
            ->whereNull('rejected_by')
            ->where('status', 'Pending');
    }

    /**
     * Request is awaiting Approver 2 (approved by A1).
     */
    public function scopeAwaitingApprover2($query)
    {
        return $query->whereNotNull('approved_by_a1')
            ->whereNull('approved_by_a2')
            ->whereNull('rejected_by')
            ->where('status', 'Pending');
    }

    public function approver1()
    {
        return $this->belongsTo(\App\Models\EmployeeMaster::class, 'approved_by_a1', 'pk');
    }

    public function approver2()
    {
        return $this->belongsTo(\App\Models\EmployeeMaster::class, 'approved_by_a2', 'pk');
    }

    public function rejectedByUser()
    {
        return $this->belongsTo(\App\Models\EmployeeMaster::class, 'rejected_by', 'pk');
    }
}

