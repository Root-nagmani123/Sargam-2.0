<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stream extends Model
{
    protected $table = 'stream_master';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    protected $fillable = ['stream_name'];
}
