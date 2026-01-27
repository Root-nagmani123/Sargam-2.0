<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientType extends Model
{
    use HasFactory;
    
    protected $table = 'mess_client_types';
    
    protected $fillable = [
        'type_name',
        'type_code',
        'description',
        'default_credit_limit',
        'is_active'
    ];
    
    protected $casts = [
        'default_credit_limit' => 'decimal:2',
        'is_active' => 'boolean'
    ];
}
