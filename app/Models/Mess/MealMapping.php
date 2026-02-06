<?php
namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MealMapping extends Model
{
    use HasFactory;
    protected $table = 'mess_meal_mappings';
    protected $fillable = ['meal_name', 'day_of_week', 'items'];
}
