<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemoTypeMaster extends Model
{
    protected $table = 'memo_type_master';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    protected $fillable = [
        'memo_type_name',
        'memo_doc_upload',
        'active_inactive'
    ];
}
