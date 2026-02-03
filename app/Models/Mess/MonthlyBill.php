<?php

namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class MonthlyBill extends Model
{
    use HasFactory;
    
    protected $table = 'mess_monthly_bills';
    
    protected $fillable = [
        'user_id',
        'bill_number',
        'month',
        'year',
        'month_year',
        'total_amount',
        'paid_amount',
        'balance',
        'status',
        'due_date',
        'paid_date',
        'remarks'
    ];
    
    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'month_year' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date'
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'pk');
    }
}
