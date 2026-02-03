<?php
namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Invoice extends Model
{
    use HasFactory;
    
    protected $table = 'mess_invoices';
    
    protected $fillable = [
        'invoice_no',
        'vendor_id',
        'buyer_id',
        'invoice_date',
        'amount',
        'paid_amount',
        'balance',
        'payment_type',
        'payment_status',
        'due_date',
        'paid_date',
        'remarks'
    ];
    
    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'invoice_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date'
    ];
    
    /**
     * Get the vendor associated with the invoice
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
    
    /**
     * Get the buyer (user) associated with the invoice
     */
    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id', 'pk');
    }
    
    /**
     * Get finance bookings related to this invoice
     */
    public function financeBookings()
    {
        return $this->hasMany(FinanceBooking::class);
    }
    
    /**
     * Check if invoice is overdue
     */
    public function isOverdue()
    {
        if (!$this->due_date) {
            return false;
        }
        
        return $this->payment_status !== 'paid' && 
               $this->due_date < now();
    }
    
    /**
     * Get the status badge color
     */
    public function getStatusColorAttribute()
    {
        return match($this->payment_status) {
            'paid' => 'success',
            'partial' => 'warning',
            'overdue' => 'danger',
            default => 'secondary'
        };
    }
}
