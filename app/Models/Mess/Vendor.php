<?php
namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;
    
    protected $table = 'mess_vendors';
    
    protected $fillable = [
        'name',
        'email',
        'contact_person',
        'phone',
        'address',
    ];
}
