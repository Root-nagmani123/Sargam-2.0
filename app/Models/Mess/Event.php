<?php
namespace App\Models\Mess;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;
    protected $table = 'mess_events';
    protected $fillable = ['name', 'description', 'event_date'];
}
