<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCredential extends Model
{
    protected $table = 'user_credentials';

    protected $primaryKey = 'pk';

    protected $guarded = [];

    public $timestamps = false;
}
