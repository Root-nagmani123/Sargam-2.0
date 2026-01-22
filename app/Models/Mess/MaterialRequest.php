<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Model;

class MaterialRequest extends Model
{
    protected $table = 'mess_material_requests';
    
    protected $fillable = [
        'request_number', 'request_date', 'store_id', 'status',
        'purpose', 'requested_by', 'approved_by', 'approved_at', 'rejection_reason'
    ];
    
    protected $casts = [
        'request_date' => 'date',
        'approved_at' => 'datetime',
    ];
    
    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
    
    public function items()
    {
        return $this->hasMany(MaterialRequestItem::class, 'material_request_id');
    }
    
    public function requester()
    {
        return $this->belongsTo(\App\Models\User::class, 'requested_by');
    }
    
    public function approver()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }
}
