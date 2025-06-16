<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Support\Htmlable;

class FrontPage extends Model
{
    use HasFactory;

    protected $table = 'fc_front_pages';

    protected $primaryKey = 'pk';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $guarded = [];
}
