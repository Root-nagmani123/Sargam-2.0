<?php
namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemSubcategory extends Model
{
    use HasFactory;
    protected $table = 'mess_item_subcategories';
    protected $fillable = ['category_id', 'name', 'description'];
}
