<?php

namespace App\Models\Mess;

use App\Models\Mess\Concerns\HasActiveStatus;
use Illuminate\Database\Eloquent\Model;

class SubStore extends Model
{
    use HasActiveStatus;

    public const STATUS_ACTIVE   = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $table = 'mess_sub_stores';
    
    protected $fillable = [
        'sub_store_name',
        'status',
    ];

}
