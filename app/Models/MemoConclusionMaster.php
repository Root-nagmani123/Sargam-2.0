<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemoConclusionMaster extends Model
{
    protected $table = 'memo_conclusion_master';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    protected $fillable = [
        'discussion_name',
        'pt_discusion',
        'active_inactive',
        'created_date',
        'modified_date'
    ];
}
