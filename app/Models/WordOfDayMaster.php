<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WordOfDayMaster extends Model
{
    use SoftDeletes;

    protected $table = 'word_of_the_days';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'hindi_text',
        'english_text',
        'sort_order',
        'active_inactive',
        'scheduled_date',
        'created_by_pk',
        'updated_by_pk',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
    ];
}

