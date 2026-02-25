<?php
namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;
    protected $table = 'mess_invoices';
    protected $fillable = ['vendor_id', 'invoice_date', 'amount', 'status'];
}
