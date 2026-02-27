<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class CreditLimit extends Model
{
    use HasFactory;
    
    protected $table = 'mess_credit_limits';
    
    protected $fillable = [
        'user_id',
        'client_type',
        'credit_limit',
        'current_balance',
        'is_active'
    ];
    
    protected $casts = [
        'credit_limit' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean'
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
