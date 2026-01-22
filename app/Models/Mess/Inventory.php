<?php
namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;
    protected $table = 'mess_inventories';
    protected $fillable = ['item_name', 'category', 'quantity', 'unit', 'expiry_date'];
}
