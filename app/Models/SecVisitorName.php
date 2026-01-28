<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecVisitorName extends Model
{
    protected $table = 'sec_visitor_names';
    protected $primaryKey = 'pk';
    public $timestamps = false;

    protected $fillable = [
        'sec_visitor_card_generated_pk',
        'visitor_name',
        'created_date',
    ];

    protected $casts = [
        'created_date' => 'datetime',
    ];

    public function visitorCard()
    {
        return $this->belongsTo(SecVisitorCardGenerated::class, 'sec_visitor_card_generated_pk', 'pk');
    }
}
