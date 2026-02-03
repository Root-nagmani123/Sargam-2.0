<?php
namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Mess\Vendor;

class Invoice extends Model
{
    use HasFactory;
    
    protected $table = 'mess_invoices';
    
    protected $fillable = [
        'invoice_no',
        'vendor_id',
        'buyer_id',
        'bill_no',
        'invoice_date',
        'amount',
        'total_amount',
        'payment_type',
        'status',
        'is_deleted',
        'created_by',
        'approved_by',
        'approved_at',
        'remarks'
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'approved_at' => 'datetime',
        'is_deleted' => 'boolean',
    ];

    /**
     * Relationship with vendor
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    /**
     * Relationship with buyer (user)
     */
    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /**
     * Relationship with creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship with approver
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope for active invoices
     */
    public function scopeActive($query)
    {
        return $query->where('is_deleted', 0);
    }

    /**
     * Scope for pending invoices
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved invoices
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
