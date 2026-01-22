<?php
namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreAllocation extends Model
{
    use HasFactory;
    protected $table = 'mess_store_allocations';
    protected $fillable = ['store_name', 'allocated_to', 'quantity', 'allocation_date'];
}
